<?php

namespace Tests;

use Facades\Statamic\Fields\BlueprintRepository;
use Facades\Tests\Factories\EntryFactory;
use Illuminate\Support\Str;
use Statamic\Facades\Collection;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Statamic\Facades\YAML;
use Statamic\Fields\Blueprint;
use Statamic\Taxonomies\LocalizedTerm;
use Statamic\Taxonomies\Taxonomy;

class RelationshipTestCase extends BaseTestCase
{
    use PreventSavingStacheItemsToDisk;

    protected $blueprints = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->createBlueprints();
        $this->createUserBlueprint();
        $this->createTaxonomies();
        $this->createTerms();
        $this->createCollections();
        $this->createCollectionEntries();
        $this->createUsers();
    }

    protected function buildBlueprint($name, $path)
    {
        $fields = YAML::parse(file_get_contents(__DIR__.'/__fixtures__/blueprints/'.$name.'.yaml'))['sections']['main']['fields'];

        $blueprint = new Blueprint();
        $blueprint->setContents([
            'fields' => $fields,
        ]);
        $blueprint->setHandle($name);

        $this->blueprints[$name] = $blueprint;

        BlueprintRepository::shouldReceive('find')->zeroOrMoreTimes()->with($path)->andReturn($blueprint);
        BlueprintRepository::shouldReceive('in')->zeroOrMoreTimes()->with($path)->andReturn(collect([
            $name => $blueprint,
        ]));
    }

    protected function createUserBlueprint()
    {
        $this->buildBlueprint('user', 'user');
    }

    protected function makeBlueprint($name)
    {
        $this->buildBlueprint($name, 'collections/'.$name);
    }

    protected function makeTaxonomyBlueprint($name)
    {
        $this->buildBlueprint($name, 'taxonomies/'.$name);
    }

    protected function createBlueprints()
    {
        $this->makeTaxonomyBlueprint('topics');
        $this->makeBlueprint('authors');
        $this->makeBlueprint('books');
        $this->makeBlueprint('conferences');
        $this->makeBlueprint('employees');
        $this->makeBlueprint('positions');
        $this->makeBlueprint('sponsors');
        $this->makeBlueprint('articles');
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

    protected function createTaxonomies()
    {
        \Statamic\Facades\Taxonomy::make('topics')->save();
    }

    protected function createTerms()
    {
        $terms = [
            'one', 'two', 'three', 'four',
        ];

        foreach ($terms as $term) {
            $title = 'Term '.Str::ucfirst($term);

            Term::make()->taxonomy('topics')->slug('topics-'.$term)->data([
                'title' => $title,
            ])->save();
        }
    }

    /**
     * Locate a taxonomy term by its slug.
     *
     * @param  string  $slug
     * @return LocalizedTerm|null
     */
    protected function getTerm($slug)
    {
        return Term::query()->where('slug', $slug)->first();
    }

    protected function createUsers()
    {
        User::make()->id('user-1')->email('user1@example.org')->save();
        User::make()->id('user-2')->email('user2@example.org')->save();
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
            [
                'title' => 'Book Three',
            ],
            [
                'title' => 'Book Four',
            ],
            [
                'title' => 'Book Five',
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

        $this->createEntries('articles', [
            [
                'title' => 'Article One',
            ],
            [
                'title' => 'Article Two',
            ],
            [
                'title' => 'Article Three',
            ],
            [
                'title' => 'Article Four',
            ],
        ]);
    }
}
