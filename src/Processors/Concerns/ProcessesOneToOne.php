<?php

namespace Stillat\Relationships\Processors\Concerns;

use Statamic\Facades\Data;
use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;

trait ProcessesOneToOne
{
    protected function processOneToOne(ComparisonResult $results, EntryRelationship $relationship)
    {
        if (! empty($results->added) && count($results->added) == 1 && $this->shouldProcessRelationship($relationship, $results->added[0])) {
            $target = $this->getEffectedEntity($relationship, $results->added[0]);

            // Evict the previous holder of this one-to-one slot.
            $previousHolderId = $target->get($relationship->rightField, null);

            if ($previousHolderId !== null && $previousHolderId !== $this->entryId) {
                $previousHolder = Data::find($previousHolderId);

                if ($previousHolder !== null) {
                    $previousHolder->set($relationship->leftField, null);
                    $previousHolder->saveQuietly();
                }
            }

            $this->setFieldValue($relationship, $target);
        }

        foreach ($results->removed as $removedId) {
            if ($this->shouldProcessRelationship($relationship, $removedId)) {
                $this->removeItemFromEntry($relationship, $this->getEffectedEntity($relationship, $removedId));
            }
        }
    }
}
