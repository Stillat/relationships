<?php

namespace Stillat\Relationships\Processors;

use Statamic\Contracts\Entries\EntryRepository;
use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;
use Stillat\Relationships\Processors\Concerns\GetsFieldValues;
use Stillat\Relationships\RelationshipManager;

class FillRelationshipsProcessor
{
    use GetsFieldValues;

    /**
     * @var RelationshipManager
     */
    protected $manager;

    /**
     * @var EntryRepository
     */
    protected $entries;

    public function __construct(RelationshipManager $manager, EntryRepository $entries)
    {
        $this->manager = $manager;
        $this->entries = $entries;
    }

    /**
     * Gets the RelationshipManager instance.
     *
     * @return RelationshipManager
     */
    public function manager()
    {
        return $this->manager;
    }

    public function fillAll()
    {
        foreach ($this->manager->getAll() as $relationships) {
            if ($relationships instanceof EntryRelationship) {
                $this->fillRelationship($relationships);
            } elseif (is_array($relationships)) {
                $this->fillRelationships($relationships);
            }
        }
    }

    public function fillCollection($collection)
    {
        $this->fillRelationships($this->manager->getRelationshipsForCollection($collection));
    }

    protected function fillRelationships($relationships)
    {
        foreach ($relationships as $relationship) {
            $this->fillRelationship($relationship);
        }
    }

    protected function fillRelationship(EntryRelationship $relationship)
    {
        $collectionEntries = $this->entries->query()
            ->whereIn('collection', [$relationship->leftCollection])
            ->where($relationship->leftField, '!=', null)
            ->get();

        if (count($collectionEntries) == 0) {
            return;
        }

        foreach ($collectionEntries as $entry) {
            $related = $this->getFieldValue($relationship->leftField, $entry, null);

            if ($related == null) {
                continue;
            }

            if (! is_array($related)) {
                $related = [$related];
            }

            $mockResults = new ComparisonResult();
            $mockResults->added = $related;

            $this->manager->processor()->setEntryId($entry->id())
                ->processRelationship($relationship, $mockResults);
        }
    }
}
