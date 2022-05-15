<?php

namespace Stillat\Relationships\Processors;

use Statamic\Contracts\Entries\EntryRepository;
use Statamic\Entries\Entry;
use Statamic\Entries\EntryCollection;
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

    protected $isDelete = false;

    protected $isDry = false;

    public function __construct(EntryRepository $entries)
    {
        $this->entries = $entries;
    }

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
        $this->entryId = $this->updatedEntry->id();

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
     * @param Entry $entry
     * @param EntryRelationship $relationship
     */
    protected function updateEntry($entry, $relationship)
    {
        UpdatingRelatedEntryEvent::dispatch($entry, $this->pristineEntry, $relationship);

        if (! $this->isDry) {
            if ($relationship->withEvents) {
                $entry->save();
            } else {
                $entry->saveQuietly();
            }
        }

        UpdatedRelatedEntryEvent::dispatch($entry, $this->pristineEntry, $relationship);
    }

    protected function getEntryData($fieldName)
    {
        $pristine = [];
        $updated = [];

        if ($this->pristineEntry != null && $this->isNewEntry == false) {
            $pristine = $this->getFieldValue($fieldName, $this->pristineEntry, []);
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

    protected function getEffectedEntries($entryIds)
    {
        /** @var EntryCollection $entries */
        $entries = $this->entries->query()->whereIn('id', $entryIds)->get();

        $this->effectedEntries = $entries->keyBy('id')->all();
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

            $this->processRelationship($relationship, $this->getEntryData($relationship->leftField));
        }
    }

    public function processRelationship($relationship, $results)
    {
        UpdatingRelationshipsEvent::dispatch($relationship, $results);

        if (! $results->hasChanges()) {
            UpdatedRelationshipsEvent::dispatch($relationship, $results);
            return;
        }

        $this->getEffectedEntries($results->getEffectedIds());

        if ($relationship->type == EntryRelationship::TYPE_MANY_TO_MANY) {
            $this->processManyToMany($results, $relationship);
        } else if ($relationship->type == EntryRelationship::TYPE_ONE_TO_ONE) {
            $this->processOneToOne($results, $relationship);
        } else if ($relationship->type == EntryRelationship::TYPE_ONE_TO_MANY) {
            $this->processOneToMany($results, $relationship);
        } else if ($relationship->type == EntryRelationship::TYPE_MANY_TO_ONE) {
            $this->processManyToOne($results, $relationship);
        }

        UpdatedRelationshipsEvent::dispatch($relationship, $results);
    }

    /**
     * @param EntryRelationship $relationship
     * @param Entry $entry
     */
    protected function removeFieldValue($relationship, $entry)
    {
        if ($entry->collectionHandle() != $relationship->rightCollection) { return; }

        $rightReference = $entry->get($relationship->rightField, null);

        if ($rightReference != $this->entryId) { return; }

        $entry->set($relationship->rightField, null);

        $this->updateEntry($entry, $relationship);
    }

    /**
     * @param EntryRelationship $relationship
     * @param Entry $entry
     */
    protected function setFieldValue($relationship, $entry)
    {
        if ($entry->collectionHandle() != $relationship->rightCollection) { return; }

        $rightReference = $entry->get($relationship->rightField, null);

        if ($rightReference !== $this->entryId) {
            $entry->set($relationship->rightField, $this->entryId);

            $this->updateEntry($entry, $relationship);
        }
    }

    /**
     * @param EntryRelationship $relationship
     * @param Entry $entry
     */
    protected function addItemToEntry($relationship, $entry)
    {
        if ($entry->collectionHandle() != $relationship->rightCollection) { return; }

        $rightReference = $entry->get($relationship->rightField, []);

        if (in_array($this->entryId, $rightReference)) { return; }

        $rightReference[] = $this->entryId;
        $entry->set($relationship->rightField, array_values($rightReference));

        $this->updateEntry($entry, $relationship);
    }

    /**
     * @param EntryRelationship $relationship
     * @param Entry $entry
     */
    protected function removeItemFromEntry($relationship, $entry)
    {
        if ($entry->collectionHandle() != $relationship->rightCollection) { return; }

        $rightReference = $entry->get($relationship->rightField, []);

        if (! is_array($rightReference)) {
            $rightReference = [$rightReference];
        }

        if (($key = array_search($this->entryId, $rightReference)) !== false) {
            unset($rightReference[$key]);

            $entry->set($relationship->rightField, array_values($rightReference));

            $this->updateEntry($entry, $relationship);
        }
    }
}
