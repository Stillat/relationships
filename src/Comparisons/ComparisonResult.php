<?php

namespace Stillat\Relationships\Comparisons;

class ComparisonResult
{
    public $same = [];
    public $added = [];
    public $removed = [];

    public function getSameCount()
    {
        return count($this->same);
    }

    public function getAddedCount()
    {
        return count($this->added);
    }

    public function getRemovedCount()
    {
        return count($this->removed);
    }

    public function hasChanges()
    {
        return count($this->added) > 0 || count($this->removed) > 0;
    }

    public function getEffectedIds()
    {
        return array_unique(array_merge($this->added, $this->removed));
    }
}
