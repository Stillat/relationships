<?php

namespace Stillat\Relationships;

use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Events\EntrySaving;
use Statamic\Events\UserDeleted;
use Statamic\Events\UserSaved;
use Statamic\Events\UserSaving;
use Statamic\Providers\AddonServiceProvider;
use Stillat\Relationships\Console\Commands\FillRelationshipsCommand;
use Stillat\Relationships\Console\Commands\ListRelationshipsCommand;
use Stillat\Relationships\Listeners\EntryDeletedListener;
use Stillat\Relationships\Listeners\EntrySavedListener;
use Stillat\Relationships\Listeners\EntrySavingListener;
use Stillat\Relationships\Listeners\UserDeletedListener;
use Stillat\Relationships\Listeners\UserSavedListener;
use Stillat\Relationships\Listeners\UserSavingListener;
use Stillat\Relationships\Processors\RelationshipProcessor;

class ServiceProvider extends AddonServiceProvider
{
    protected $listen = [
        EntrySaving::class => [
            EntrySavingListener::class,
        ],
        EntrySaved::class => [
            EntrySavedListener::class,
        ],
        EntryDeleted::class => [
            EntryDeletedListener::class,
        ],

        UserSaving::class => [
            UserSavingListener::class,
        ],
        UserSaved::class => [
            UserSavedListener::class,
        ],
        UserDeleted::class => [
            UserDeletedListener::class,
        ],
    ];

    protected $commands = [
        FillRelationshipsCommand::class,
        ListRelationshipsCommand::class,
    ];

    public function register()
    {
        $this->app->singleton(RelationshipManager::class, function ($app) {
            return new RelationshipManager($app->make(RelationshipProcessor::class));
        });
    }
}
