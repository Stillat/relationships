<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Contracts\Auth\User;
use Statamic\Events\UserSaving;
use Statamic\Facades\User as UserFacade;
use Stillat\Relationships\RelationshipManager;

class UserSavingListener extends BaseListener
{
    /**
     * @var RelationshipManager
     */
    protected $manager;

    public function __construct(RelationshipManager $manager)
    {
        $this->manager = $manager;
    }

    public function handle(UserSaving $event)
    {
        /** @var User $user */
        $user = $event->user;

        if (! $this->manager->hasUserRelationships()) {
            return;
        }

        $isUpdating = $user->id() !== null;

        if ($isUpdating) {
            $foundUser = UserFacade::find($event->user->id());

            if ($foundUser === null) {
                $isUpdating = false;
            } else {
                $user = clone $foundUser;
                $isUpdating = true;
            }
        }

        $user = $this->checkForDatabaseObject($user);

        $this->manager->processor()->setIsDeleting(false)
            ->setPristineDetails($user, ! $isUpdating);
    }
}
