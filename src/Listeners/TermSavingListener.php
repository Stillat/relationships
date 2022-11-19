<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Events\TermSaving;
use Statamic\Facades\Term as TermFacade;
use Statamic\Taxonomies\Term;
use Stillat\Relationships\RelationshipManager;

class TermSavingListener
{
    /** @var RelationshipManager */
    protected $manager;

    public function __construct(RelationshipManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(TermSaving $event)
    {
        /** @var Term $term */
        $term = $event->term;

        if (! $this->manager->hasTermRelationships()) {
            return;
        }

        $isUpdating = $term->id() !== null;

        if ($isUpdating) {
            $foundTerm = TermFacade::find($event->term->id());

            if ($foundTerm === null) {
                $isUpdating = false;
            } else {
                $term = clone $foundTerm;
                $isUpdating = true;
            }
        }

        $this->manager->processor()->setIsDeleting(false)
            ->setPristineDetails($term, ! $isUpdating);
    }
}
