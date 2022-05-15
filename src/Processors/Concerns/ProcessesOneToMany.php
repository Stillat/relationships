<?php

namespace Stillat\Relationships\Processors\Concerns;

use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;

trait ProcessesOneToMany
{
    protected function processOneToMany(ComparisonResult $results, EntryRelationship $relationship)
    {
        foreach ($results->added as $addedId) {
            if (array_key_exists($addedId, $this->effectedEntries)) {
                $this->setFieldValue($relationship, $this->effectedEntries[$addedId]);
            }
        }

        foreach ($results->removed as $removedId) {
            if (array_key_exists($removedId, $this->effectedEntries)) {
                $this->removeFieldValue($relationship, $this->effectedEntries[$removedId]);
            }
        }
    }
}
