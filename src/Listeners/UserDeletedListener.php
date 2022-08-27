<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Contracts\Auth\User;
use Statamic\Events\UserDeleted;
use Stillat\Relationships\RelationshipManager;

class UserDeletedListener
{
    protected $manager;

    public function __construct(RelationshipManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(UserDeleted $event)
    {
        /** @var User $user */
        $user = $event->user;

        if (! $this->manager->hasUserRelationships()) {
            return;
        }

        $relationships = $this->manager->getAllUserRelationships();

        $this->manager->processor()->setIsDeleting()->setEntryId($user->id())
            ->setPristineDetails($user, false)->process($relationships);
    }
}