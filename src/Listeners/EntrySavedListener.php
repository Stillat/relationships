<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Entries\Entry;
use Statamic\Events\EntrySaved;
use Stillat\Relationships\RelationshipManager;

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
        /** @var Entry $entry */
        $entry = $event->entry;
        $collection = $entry->collection();

        if ($collection == null) { return; }

        $this->manager->processor()->setUpdatedEntryDetails($entry);

        $handle = $collection->handle();
        $relationships = $this->manager->getRelationshipsForCollection($handle);

        $this->manager->processor()->process($relationships);
    }
}
