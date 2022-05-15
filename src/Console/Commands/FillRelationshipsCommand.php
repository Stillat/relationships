<?php

namespace Stillat\Relationships\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Stillat\Relationships\Events\UpdatedRelatedEntryEvent;
use Stillat\Relationships\Events\UpdatedRelationshipsEvent;
use Stillat\Relationships\Events\UpdatingRelatedEntryEvent;
use Stillat\Relationships\Events\UpdatingRelationshipsEvent;
use Stillat\Relationships\Processors\FillRelationshipsProcessor;
use Symfony\Component\Console\Output\OutputInterface;

class FillRelationshipsCommand extends Command
{
    protected $signature = 'relate:fill 
                                {collection? : An optional collection handle}
                                {--dry : When present, no entries will be updated}';
    protected $description = 'Fills in any missing values from newly created relationships';

    /**
     * @var FillRelationshipsProcessor
     */
    protected $processor;

    public function __construct(FillRelationshipsProcessor $processor)
    {
        parent::__construct();

        $this->processor = $processor;
    }

    private function setupNormalVerbosity()
    {
        $this->info('Updating relationships. This may take a while if you have a lot of entries.');
        $this->newLine();

        $loggedMessages = [];

        Event::listen(UpdatingRelationshipsEvent::class, function (UpdatingRelationshipsEvent $event) use (&$loggedMessages) {
            $message = "Updating relationship: {$event->relationship->getDescription()}...";

            if (! in_array($message, $loggedMessages)) {
                $this->info($message);
                $loggedMessages[] = $message;
            }
        });

        Event::listen(UpdatedRelationshipsEvent::class, function (UpdatedRelationshipsEvent $event) use (&$loggedMessages) {
            $message = "Updating relationship: {$event->relationship->getDescription()}... done!";

            if (! in_array($message, $loggedMessages)) {
                $this->info($message);
                $loggedMessages[] = $message;
            }
        });
    }

    private function setupVerboseLogging()
    {
        $this->info('Updating relationships. This may take a while if you have a lot of entries.');
        $this->newLine();

        Event::listen(UpdatingRelationshipsEvent::class, function (UpdatingRelationshipsEvent $event) {
            $this->info("Updating relationship: {$event->relationship->getDescription()}...");
            $this->line("    Added: {$event->results->getAddedCount()} Removed: {$event->results->getRemovedCount()} Same: {$event->results->getSameCount()}");
        });

        Event::listen(UpdatedRelationshipsEvent::class, function (UpdatedRelationshipsEvent $event) use (&$loggedMessages) {
            $this->info("Updating relationship: {$event->relationship->getDescription()}... done!");
        });
    }

    public function handle()
    {
        $verbosity = $this->getOutput()->getVerbosity();

        if ($verbosity == OutputInterface::VERBOSITY_NORMAL) {
            $this->setupNormalVerbosity();
        } else if ($verbosity > OutputInterface::VERBOSITY_NORMAL) {
            $this->setupVerboseLogging();
        }

        if ($verbosity == OutputInterface::VERBOSITY_DEBUG) {
            Event::listen(UpdatingRelatedEntryEvent::class, function (UpdatingRelatedEntryEvent $event) {
                $this->line("    Updating entry: {$event->updatedEntry->id()}...");
            });
            Event::listen(UpdatedRelatedEntryEvent::class, function (UpdatedRelatedEntryEvent $event) {
                $this->line("    Updating entry: {$event->updatedEntry->id()}... done!");
            });
        } else if ($verbosity >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
            Event::listen(UpdatingRelatedEntryEvent::class, function (UpdatingRelatedEntryEvent $event) {
                $this->line("    Updating entry: {$event->updatedEntry->id()}");
            });
        }

        $this->processor->manager()->processor()->setIsDryRun($this->option('dry'));

        $collection = $this->argument('collection');

        if ($collection != null && is_string($collection)) {
            $this->processor->fillCollection($collection);
            return;
        }

        $this->processor->fillAll();
    }
}