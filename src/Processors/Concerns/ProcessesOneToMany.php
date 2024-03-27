<?php

namespace Stillat\Relationships\Processors\Concerns;

use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;

trait ProcessesOneToMany
{
    protected function processOneToMany(ComparisonResult $results, EntryRelationship $relationship)
    {
        foreach ($results->removed as $removedId) {
            if ($this->shouldProcessRelationship($relationship, $removedId)) {
                $this->removeFieldValue($relationship, $this->getEffectedEntity($relationship, $removedId));
            }
        }

        foreach ($results->added as $addedId) {
            if ($this->shouldProcessRelationship($relationship, $addedId)) {
                $this->setFieldValue($relationship, $this->getEffectedEntity($relationship, $addedId));
            }
        }
    }
}
