<?php

namespace Stillat\Relationships\Processors\Concerns;

use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;

trait ProcessesManyToOne
{
    protected function processManyToOne(ComparisonResult $results, EntryRelationship $relationship)
    {
        if (! empty($results->added) && count($results->added) == 1 && $this->shouldProcessRelationship($relationship, $results->added[0])) {
            $this->addItemToEntry($relationship, $this->getEffectedEntity($relationship, $results->added[0]));
        }

        foreach ($results->removed as $removedId) {
            if ($this->shouldProcessRelationship($relationship, $removedId)) {
                $this->removeItemFromEntry($relationship, $this->getEffectedEntity($relationship, $removedId));
            }
        }
    }
}
