<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Events\TermDeleted;
use Statamic\Taxonomies\Term;
use Stillat\Relationships\RelationshipManager;

class TermDeletedListener
{
    /** @var RelationshipManager */
    protected $manager;

    public static $break = false;

    public function __construct(RelationshipManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(TermDeleted $event)
    {
        /** @var Term $term */
        $term = $event->term;

        if (! $this->manager->hasTermRelationships()) {
            return;
        }

        $relationships = $this->manager->getAllTermRelationships();

        $this->manager->processor()->setIsDeleting()->setEntryId($term->slug())
            ->setPristineDetails($term, false)->process($relationships);
    }
}
