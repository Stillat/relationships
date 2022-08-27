<?php

namespace Stillat\Relationships\Console\Commands;

use Illuminate\Console\Command;
use Stillat\Relationships\RelationshipManager;

class ListRelationshipsCommand extends Command
{
    protected $signature = 'relate:list
                                {collection? : An optional collection handle}';
    protected $description = 'Lists all defined entry relationships';

    protected $manager;

    public function __construct(RelationshipManager $manager)
    {
        parent::__construct();

        $this->manager = $manager;
    }

    public function handle()
    {
        $relationships = $this->manager->getAllRelationships();

        $collection = $this->argument('collection');

        if ($collection != null && is_string($collection)) {
            $relationships = $this->manager->getRelationshipsForCollection($collection);
        }

        $list = [];

        foreach ($relationships as $relationship) {
            $list[] = [
                'index' => $relationship->index,
                'left_collection' => '['.$relationship->leftType.'].'.$relationship->leftCollection,
                'left_field' => '['.$relationship->leftType.'].'.$relationship->leftField,
                'right_collection' => '['.$relationship->rightType.'].'.$relationship->rightCollection,
                'right_field' => $relationship->rightField,
                'type_description' => $relationship->getRelationshipDescription(),
                'with_events' => $relationship->withEvents ? 'Yes' : 'No',
                'allow_delete' => $relationship->allowDelete ? 'Yes' : 'No',
                'automatic_inverse' => $relationship->isAutomaticInverse ? "Yes ({$relationship->inverseIndex})" : 'No',
            ];
        }

        $this->table([
            '',
            'Primary Collection',
            'Related Collection',
            'Primary Field',
            'Related Field',
            'Relationship',
            'With Events?',
            'Allow Deletes?',
            'Is Automatic Inverse?',
        ], $list);
    }
}
