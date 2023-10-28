<?php

namespace Stillat\Relationships\Processors;

use Illuminate\Support\Str;
use Statamic\Contracts\Auth\User;
use Statamic\Contracts\Entries\EntryRepository;
use Statamic\Contracts\Taxonomies\TermRepository;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Taxonomies\Term;
use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\Comparisons\ListComparator;
use Stillat\Relationships\EntryRelationship;
use Stillat\Relationships\Events\UpdatedRelatedEntryEvent;
use Stillat\Relationships\Events\UpdatedRelationshipsEvent;
use Stillat\Relationships\Events\UpdatingRelatedEntryEvent;
use Stillat\Relationships\Events\UpdatingRelationshipsEvent;
use Stillat\Relationships\Processors\Concerns\GetsFieldValues;
use Stillat\Relationships\Processors\Concerns\ProcessesManyToMany;
use Stillat\Relationships\Processors\Concerns\ProcessesManyToOne;
use Stillat\Relationships\Processors\Concerns\ProcessesOneToMany;
use Stillat\Relationships\Processors\Concerns\ProcessesOneToOne;

class RelationshipProcessor
{
    use ProcessesManyToMany,
        ProcessesOneToOne,
        ProcessesManyToOne,
        ProcessesOneToMany,
        GetsFieldValues;

    /**
     * @var EntryRepository
     */
    protected $entries;

    /**
     * Indicates if the current entry being saved is new.
     *
     * @var bool
     */
    protected $isNewEntry = false;

    /**
     * @var Entry|null
     */
    protected $pristineEntry = null;

    /**
     * The current entry identifier.
     *
     * @var string|null
     */
    protected $entryId = null;

    /**
     * @var Entry|null
     */
    protected $updatedEntry = null;

    /**
     * All entries that are affected by the current change.
     *
     * @var Entry[]
     */
    protected $effectedEntries = [];

    /**
     * All users that are affected by the current change.
     *
     * @var User[]
     */
    protected $effectedUsers = [];

    /**
     * All terms that are affected by the current change.
     *
     * @var Term[]
     */
    protected $effectedTerms = [];

    protected $isDelete = false;

    protected $isDry = false;

    public function setIsDeleting($isDeleting = true)
    {
        $this->isDelete = $isDeleting;

        return $this;
    }

    public function setPristineDetails($entryData, $isNew)
    {
        if ($isNew) {
            return $this->setPristineNewDetails($entryData);
        }

        return $this->setPristineUpdateDetails($entryData);
    }

    public function setPristineNewDetails($entryData)
    {
        $this->isNewEntry = true;
        $this->pristineEntry = $entryData;

        return $this;
    }

    public function setIsDryRun($isDry = true)
    {
        $this->isDry = $isDry;

        return $this;
    }

    public function setPristineUpdateDetails($entryData)
    {
        $this->isNewEntry = false;
        $this->pristineEntry = $entryData;

        return $this;
    }

    public function setEntryId($entryId)
    {
        $this->entryId = $entryId;

        return $this;
    }

    public function setUpdatedEntryDetails($entryData)
    {
        $this->updatedEntry = $entryData;

        if ($entryData instanceof Term) {
            $this->entryId = $this->updatedEntry->slug();
        } else {
            $this->entryId = $this->updatedEntry->id();
        }

        return $this;
    }

    public function getPristineEntry()
    {
        return $this->pristineEntry;
    }

    public function getUpdatedEntry()
    {
        return $this->updatedEntry;
    }

    /**
     * @param  Entry  $entry
     * @param  EntryRelationship  $relationship
     */
    protected function updateEntry($entry, $relationship)
    {
        UpdatingRelatedEntryEvent::dispatch($entry, $this->pristineEntry, $relationship);

        if (! $this->isDry) {
            if ($relationship->withEvents) {
                $entry->save();
            } else {
                if ($entry instanceof LocalizedTerm) {
                    $entry->term()->saveQuietly();
                } else {
                    $entry->saveQuietly();
                }
            }
        }

        UpdatedRelatedEntryEvent::dispatch($entry, $this->pristineEntry, $relationship);
    }

    protected function getEntryData($relationship)
    {
        $fieldName = $relationship->leftField;
        $pristine = [];
        $updated = [];

        if ($this->pristineEntry != null && $this->isNewEntry == false) {
            $pristine = $this->getPristineValue($fieldName, $this->pristineEntry, []);
        }

        if ($this->isDelete) {
            $deletedResults = new ComparisonResult();

            if (! is_array($pristine)) {
                $pristine = [$pristine];
            }

            $deletedResults->removed = $pristine;

            return $deletedResults;
        }

        if ($this->updatedEntry != null) {
            $updated = $this->getFieldValue($fieldName, $this->updatedEntry, []);
        }

        return ListComparator::compare($pristine, $updated);
    }

    /**
     * @param  EntryRelationship  $relationship
     * @param  string[]  $entryIds
     * @return void
     */
    protected function getEffectedEntries($relationship, $entryIds)
    {
        // The right-hand side of the relationship will indicate what is being updated.
        if ($relationship->rightType == 'entry') {
            if ($this->entries == null) {
                $this->entries = app(EntryRepository::class);
            }

            /** @var EntryCollection $entries */
            $entries = $this->entries->query()->whereIn('id', $entryIds)->get();

            $this->effectedEntries = $entries->keyBy('id')->all();
        } elseif ($relationship->rightType == 'user') {
            $users = $this->getUsersByIds($entryIds);

            $this->effectedUsers = $users->keyBy('id')->all();
        } elseif ($relationship->rightType == 'term') {
            $terms = $this->getTermsByIds($relationship, $entryIds);

            $this->effectedTerms = $terms->keyBy('slug')->all();
        }
    }

    private function getUsersByIds($userIds)
    {
        $users = [];

        foreach ($userIds as $userId) {
            $user = \Statamic\Facades\User::find($userId);

            if ($user != null) {
                $users[] = $user;
            }
        }

        return collect($users);
    }

    private function getTermsByIds(EntryRelationship $relationship, $termIds)
    {
        $terms = [];

        /** @var TermRepository $termsRepository */
        $termsRepository = app(TermRepository::class);

        foreach ($termIds as $termId) {
            $term = $termsRepository->whereTaxonomy($relationship->rightCollection)->where('slug', $termId)->first();

            if ($term != null) {
                $terms[] = $term;
            }
        }

        return collect($terms);
    }

    public function process($relationships)
    {
        if (empty($relationships)) {
            return;
        }

        /** @var EntryRelationship $relationship */
        foreach ($relationships as $relationship) {
            if ($this->isDelete && ! $relationship->allowDelete) {
                continue;
            }

            $this->processRelationship($relationship, $this->getEntryData($relationship));
        }
    }

    public function processRelationship($relationship, $results)
    {
        UpdatingRelationshipsEvent::dispatch($relationship, $results);

        if (! $results->hasChanges()) {
            UpdatedRelationshipsEvent::dispatch($relationship, $results);

            return;
        }

        $this->getEffectedEntries($relationship, $results->getEffectedIds());

        if ($relationship->type == EntryRelationship::TYPE_MANY_TO_MANY) {
            $this->processManyToMany($results, $relationship);
        } elseif ($relationship->type == EntryRelationship::TYPE_ONE_TO_ONE) {
            $this->processOneToOne($results, $relationship);
        } elseif ($relationship->type == EntryRelationship::TYPE_ONE_TO_MANY) {
            $this->processOneToMany($results, $relationship);
        } elseif ($relationship->type == EntryRelationship::TYPE_MANY_TO_ONE) {
            $this->processManyToOne($results, $relationship);
        }

        UpdatedRelationshipsEvent::dispatch($relationship, $results);
    }

    /**
     * Determines if the relationship should be processed for the provided entitiy.
     *
     * @param  EntryRelationship  $relationship  The relationship.
     * @param  string  $id  The related entity ID.
     * @return bool
     */
    protected function shouldProcessRelationship(EntryRelationship $relationship, $id)
    {
        if ($relationship->rightType == 'entry' && ! array_key_exists($id, $this->effectedEntries)) {
            return false;
        }

        if ($relationship->rightType == 'user' && ! array_key_exists($id, $this->effectedUsers)) {
            return false;
        }

        if ($relationship->rightType == 'term' && ! array_key_exists($id, $this->effectedTerms)) {
            return false;
        }

        return true;
    }

    protected function getEffectedEntity(EntryRelationship $relationship, $id)
    {
        if ($relationship->rightType == 'entry') {
            return $this->effectedEntries[$id];
        } elseif ($relationship->rightType == 'user') {
            return $this->effectedUsers[$id];
        } elseif ($relationship->rightType == 'term') {
            return $this->effectedTerms[$id];
        }

        return null;
    }

    /**
     * @param  EntryRelationship  $relationship
     * @param  Entry  $entry
     */
    protected function removeFieldValue($relationship, $entry)
    {
        if ($relationship->rightType == 'entry') {
            if ($entry->collectionHandle() != $relationship->rightCollection) {
                return;
            }
        }

        $rightReference = $entry->get($relationship->rightField, null);

        if ($rightReference != $this->entryId) {
            return;
        }

        $entry->set($relationship->rightField, null);

        $this->updateEntry($entry, $relationship);
    }

    /**
     * @param  EntryRelationship  $relationship
     * @param  Entry  $entry
     */
    protected function setFieldValue($relationship, $entry)
    {
        if ($relationship->rightType == 'entry') {
            if ($entry->collectionHandle() != $relationship->rightCollection) {
                return;
            }
        }

        $rightReference = $entry->get($relationship->rightField, null);

        if ($rightReference !== $this->entryId) {
            $entry->set($relationship->rightField, $this->entryId);

            $this->updateEntry($entry, $relationship);
        }
    }

    /**
     * @param  EntryRelationship  $relationship
     * @param  Entry|User  $entry
     */
    protected function addItemToEntry($relationship, $entry)
    {
        if ($relationship->rightType == 'entry') {
            if ($entry->collectionHandle() != $relationship->rightCollection) {
                return;
            }
        }

        $rightReference = $entry->get($relationship->rightField, []);

        if ($rightReference == null) {
            $rightReference = [];
        }

        if (is_string($rightReference) && Str::startsWith($rightReference, '[') && Str::endsWith($rightReference, ']')) {
            $rightReference = json_decode($rightReference, true);
        }

        if (in_array($this->entryId, $rightReference)) {
            return;
        }

        $rightReference[] = $this->entryId;
        $entry->set($relationship->rightField, array_values($rightReference));

        $this->updateEntry($entry, $relationship);
    }

    /**
     * @param  EntryRelationship  $relationship
     * @param  Entry  $entry
     */
    protected function removeItemFromEntry($relationship, $entry)
    {
        if ($relationship->rightType == 'entry') {
            if ($entry->collectionHandle() != $relationship->rightCollection) {
                return;
            }
        }

        $rightReference = $entry->get($relationship->rightField, []);

        if (! is_array($rightReference)) {
            $rightReference = [$rightReference];
        }

        if (($key = array_search($this->entryId, $rightReference)) !== false) {
            unset($rightReference[$key]);

            $entry->set($relationship->rightField, array_values($rightReference));

            $this->updateEntry($entry, $relationship);
        } else {
            $entry->set($relationship->rightField, null);
            $this->updateEntry($entry, $relationship);
        }
    }
}
