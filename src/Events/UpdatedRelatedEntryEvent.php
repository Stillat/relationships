<?php

namespace Stillat\Relationships\Events;

use Statamic\Entries\Entry;
use Statamic\Events\Event;
use Stillat\Relationships\EntryRelationship;

class UpdatedRelatedEntryEvent extends Event
{
    /**
     * @var Entry|null
     */
    public $updatedEntry;

    /**
     * @var Entry|null
     */
    public $relatedEntry;

    /**
     * @var EntryRelationship
     */
    public $relationship;

    public function __construct($updatedEntry, $relatedEntry, $relationship)
    {
        $this->updatedEntry = $updatedEntry;
        $this->relatedEntry = $relatedEntry;
        $this->relationship = $relationship;
    }
}