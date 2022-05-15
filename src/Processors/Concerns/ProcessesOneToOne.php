<?php

namespace Stillat\Relationships\Processors\Concerns;

use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;

trait ProcessesOneToOne
{
    protected function processOneToOne(ComparisonResult $results, EntryRelationship $relationship)
    {
        if (! empty($results->added) && count($results->added) == 1 && array_key_exists($results->added[0], $this->effectedEntries)) {
            $this->setFieldValue($relationship, $this->effectedEntries[$results->added[0]]);
        }

        foreach ($results->removed as $removedId) {
            if (array_key_exists($removedId, $this->effectedEntries)) {
                $this->removeItemFromEntry($relationship, $this->effectedEntries[$removedId]);
            }
        }
    }
}
