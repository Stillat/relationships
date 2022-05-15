<?php

namespace Stillat\Relationships\Events;

use Statamic\Events\Event;
use Stillat\Relationships\Comparisons\ComparisonResult;
use Stillat\Relationships\EntryRelationship;

class UpdatedRelationshipsEvent extends Event
{
    /**
     * @var EntryRelationship
     */
    public $relationship;

    /**
     * @var ComparisonResult
     */
    public $results;

    public function __construct($relationship, $results)
    {
        $this->relationship = $relationship;
        $this->results = $results;
    }
}
