<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Contracts\Auth\User;
use Statamic\Events\UserSaved;
use Stillat\Relationships\RelationshipManager;

class UserSavedListener
{
    /**
     * @var RelationshipManager
     */
    protected $manager;

    public function __construct(RelationshipManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(UserSaved $event)
    {
        /** @var User $user */
        $user = $event->user;

        if (! $this->manager->hasUserRelationships()) {
            return;
        }

        $this->manager->processor()->setUpdatedEntryDetails($user);

        $relationships = $this->manager->getAllUserRelationships();

        $this->manager->processor()->process($relationships);
    }
}
