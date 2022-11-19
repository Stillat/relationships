<?php

namespace Stillat\Relationships\Processors\Concerns;

use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;

trait ProcessesOneToOne
{
    protected function processOneToOne(ComparisonResult $results, EntryRelationship $relationship)
    {
        if (! empty($results->added) && count($results->added) == 1 && $this->shouldProcessRelationship($relationship, $results->added[0])) {
            $this->setFieldValue($relationship, $this->getEffectedEntity($relationship, $results->added[0]));
        }

        foreach ($results->removed as $removedId) {
            if ($this->shouldProcessRelationship($relationship, $removedId)) {
                $this->removeItemFromEntry($relationship, $this->getEffectedEntity($relationship, $removedId));
            }
        }
    }
}
