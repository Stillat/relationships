<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Entries\Entry;
use Statamic\Events\EntryDeleted;
use Stillat\Relationships\RelationshipManager;

class EntryDeletedListener
{

    /**
     * @var RelationshipManager
     */
    protected $manager;

    public function __construct(RelationshipManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(EntryDeleted $event)
    {
        /** @var Entry $entry */
        $entry = $event->entry;
        $collection = $entry->collectionHandle();

        if (! $this->manager->hasRelationshipsForCollection($collection)) {
            return;
        }

        $relationships = $this->manager->getRelationshipsForCollection($collection);

        $this->manager->processor()->setIsDeleting()->setEntryId($entry->id())
            ->setPristineDetails($entry, false)->process($relationships);

    }
}