<?php

namespace Stillat\Relationships\Processors\Concerns;

use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;

trait ProcessesManyToMany
{
    protected function processManyToMany(ComparisonResult $results, EntryRelationship $relationship)
    {
        $this->processingManyToMany = true;

        foreach ($results->added as $addedId) {
            if (! $this->shouldProcessRelationship($relationship, $addedId)) {
                continue;
            }

            $this->addItemToEntry($relationship, $this->getEffectedEntity($relationship, $addedId));
        }

        foreach ($results->removed as $removedId) {
            if (! $this->shouldProcessRelationship($relationship, $removedId)) {
                continue;
            }

            $this->removeItemFromEntry($relationship, $this->getEffectedEntity($relationship, $removedId));
        }

        $this->processingManyToMany = false;
    }
}
