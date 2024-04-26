<?php

namespace Stillat\Relationships\Processors\Concerns;

use Statamic\Facades\Data;
use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;

trait ProcessesOneToMany
{
    protected function processOneToMany(ComparisonResult $results, EntryRelationship $relationship)
    {
        foreach ($results->removed as $removedId) {
            if ($this->shouldProcessRelationship($relationship, $removedId)) {
                $this->dependencies[] = $removedId;
                $this->dependencies[] = $this->getDependency($relationship, $removedId);
                $this->removeFieldValue($relationship, $this->getEffectedEntity($relationship, $removedId));
            }
        }

        foreach ($results->added as $addedId) {
            if ($this->shouldProcessRelationship($relationship, $addedId)) {
                $this->dependencies[] = $addedId;
                $dependent = Data::find($this->getDependency($relationship, $addedId));

                if ($this->withDependent && $dependent !== null && $inverse = $relationship->getInverse()) {
                    $leftReference = $dependent->get($relationship->leftField);

                    if (($key = array_search($addedId, $leftReference)) !== false) {
                        unset($leftReference[$key]);
                        $dependent->set($relationship->leftField, array_values($leftReference));

                        $dependent->saveQuietly();
                    }
                }

                $this->setFieldValue($relationship, $this->getEffectedEntity($relationship, $addedId));
            }
        }
    }
}
