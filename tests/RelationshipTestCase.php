<?php

namespace Tests;

use Facades\Statamic\Fields\BlueprintRepository;
use Facades\Tests\Factories\EntryFactory;
use Statamic\Facades\Collection;
use Statamic\Facades\YAML;
use Statamic\Fields\Blueprint;

class RelationshipTestCase extends BaseTestCase
{
    use PreventSavingStacheItemsToDisk;

    protected $blueprints = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->createBlueprints();
        $this->createCollections();
        $this->createCollectionEntries();
    }

    protected function makeBlueprint($name)
    {
        $fields = YAML::parse(file_get_contents(__DIR__.'/__fixtures__/blueprints/'.$name.'.yaml'))['sections']['main']['fields'];

        $blueprint = new Blueprint();
        $blueprint->setContents([
            'fields' => $fields,
        ]);
        $blueprint->setHandle($name);

        $this->blueprints[$name] = $blueprint;

        BlueprintRepository::shouldReceive('in')->with('collections/'.$name)->andReturn(collect([
            $name => $blueprint,
        ]));
    }

    protected function createBlueprints()
    {
        $this->makeBlueprint('authors');
        $this->makeBlueprint('books');
        $this->makeBlueprint('conferences');
        $this->makeBlueprint('employees');
        $this->makeBlueprint('positions');
        $this->makeBlueprint('sponsors');
    }

    protected function createCollections()
    {
        Collection::make('authors')->routes('authors/{slug}')->save();
        Collection::make('books')->routes('books/{slug}')->save();
        Collection::make('conferences')->routes('conferences/{slug}')->save();
        Collection::make('employees')->routes('employees/{slug}')->save();
        Collection::make('positions')->routes('positions/{slug}')->save();
        Collection::make('sponsors')->routes('sponsors/{slug}')->save();
    }

    private function createEntry($collection, $id, $data)
    {
        EntryFactory::collection($collection)->id($id)->slug($id)->data($data)->create();
    }

    private function createEntries($collection, $entries)
    {
        $count = 1;
        foreach ($entries as $entry) {
            $this->createEntry($collection, $collection.'-'.$count, $entry);
            $count += 1;
        }
    }

    protected function createCollectionEntries()
    {
        $this->createEntries('authors', [
            [
                'title' => 'Author One',
            ],
            [
                'title' => 'Author Two',
            ],
        ]);

        $this->createEntries('books', [
            [
                'title' => 'Book One',
            ],
            [
                'title' => 'Book Two',
            ],
        ]);

        $this->createEntries('conferences', [
            [
                'title' => 'Conference One',
            ],
            [
                'title' => 'Conference Two',
            ],
        ]);

        $this->createEntries('employees', [
            [
                'title' => 'Employee One',
            ],
            [
                'title' => 'Employee Two',
            ],
        ]);

        $this->createEntries('positions', [
            [
                'title' => 'Position One',
            ],
            [
                'title' => 'Position Two',
            ],
        ]);

        $this->createEntries('sponsors', [
            [
                'title' => 'Sponsor One',
            ],
            [
                'title' => 'Sponsor Two',
            ],
        ]);
    }
}
