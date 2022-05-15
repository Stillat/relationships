<?php

namespace Stillat\Relationships\Processors\Concerns;

use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;

trait ProcessesManyToMany
{
    protected function processManyToMany(ComparisonResult $results, EntryRelationship $relationship)
    {
        foreach ($results->added as $addedId) {
            if (! array_key_exists($addedId, $this->effectedEntries)) { continue; }

            $this->addItemToEntry($relationship, $this->effectedEntries[$addedId]);
        }

        foreach ($results->removed as $removedId) {
            if (! array_key_exists($removedId, $this->effectedEntries)) { continue; }

            $this->removeItemFromEntry($relationship, $this->effectedEntries[$removedId]);
        }
    }
}
