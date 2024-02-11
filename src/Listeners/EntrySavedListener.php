<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Entries\Entry;
use Statamic\Events\EntrySaved;
use Stillat\Relationships\RelationshipManager;
use Stillat\Relationships\Support\Facades\EventStack;

class EntrySavedListener
{
    /**
     * @var RelationshipManager
     */
    protected $manager;

    public function __construct(RelationshipManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(EntrySaved $event)
    {
        EventStack::decrement();

        /** @var Entry $entry */
        $entry = $event->entry;
        $collection = $entry->collection();

        if ($collection == null) {
            return;
        }

        if (EventStack::count() > 0) {
            return;
        }

        $this->manager->processor()->setUpdatedEntryDetails($entry);

        $handle = $collection->handle();
        $relationships = $this->manager->getRelationshipsForCollection($handle);

        $this->manager->processor()->process($relationships);
    }
}
