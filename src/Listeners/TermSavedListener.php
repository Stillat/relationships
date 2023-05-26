<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Events\TermSaved;
use Statamic\Taxonomies\Term;
use Stillat\Relationships\RelationshipManager;

class TermSavedListener
{
    /** @var RelationshipManager */
    protected $manager;

    public function __construct(RelationshipManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(TermSaved $event)
    {
        /** @var Term $term */
        $term = $event->term;

        if (! $this->manager->hasTermRelationships()) {
            return;
        }

        $this->manager->processor()->setUpdatedEntryDetails($term);

        $relationships = $this->manager->getTermRelationshipsFor($term->taxonomy()->handle());

        $this->manager->processor()->process($relationships);
    }
}
