<?php

namespace Tests;

use Facades\Tests\Factories\EntryFactory;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Stillat\Relationships\Support\Facades\Relate;

/**
 * @see https://github.com/Stillat/relationships/issues/43
 */
class NumericIdTest extends RelationshipTestCase
{
    protected function createCollectionEntries()
    {
        $this->createEntry('authors', '1', ['title' => 'Author One']);
        $this->createEntry('authors', '2', ['title' => 'Author Two']);

        $this->createEntry('books', '3', ['title' => 'Book One']);
        $this->createEntry('books', '4', ['title' => 'Book Two']);
        $this->createEntry('books', '5', ['title' => 'Book Three']);
        $this->createEntry('books', '6', ['title' => 'Book Four']);
        $this->createEntry('books', '7', ['title' => 'Book Five']);

        $this->createEntry('conferences', '8', ['title' => 'Conference One']);
        $this->createEntry('conferences', '9', ['title' => 'Conference Two']);

        $this->createEntry('sponsors', '10', ['title' => 'Sponsor One']);
        $this->createEntry('sponsors', '11', ['title' => 'Sponsor Two']);

        $this->createEntry('employees', '12', ['title' => 'Employee One']);
        $this->createEntry('employees', '13', ['title' => 'Employee Two']);

        $this->createEntry('positions', '14', ['title' => 'Position One']);
        $this->createEntry('positions', '15', ['title' => 'Position Two']);

        $this->createEntry('articles', '16', ['title' => 'Article One']);
        $this->createEntry('articles', '17', ['title' => 'Article Two']);
        $this->createEntry('articles', '18', ['title' => 'Article Three']);
        $this->createEntry('articles', '19', ['title' => 'Article Four']);
    }

    private function createEntry($collection, $id, $data)
    {
        EntryFactory::collection($collection)->id($id)->slug('slug-'.$id)->data($data)->create();
    }

    public function test_one_to_many_with_numeric_ids()
    {
        Relate::clear()
            ->oneToMany('books.author', 'authors.books');

        Entry::find('3')->set('author', '1')->save();

        $this->assertSame(['3'], Entry::find('1')->get('books', []));

        Entry::find('4')->set('author', '1')->save();

        $this->assertSame(['3', '4'], Entry::find('1')->get('books', []));

        Entry::find('4')->set('author', '2')->save();

        $this->assertSame(['3'], Entry::find('1')->get('books', []));
        $this->assertSame(['4'], Entry::find('2')->get('books', []));

        Entry::find('3')->set('author', null)->save();
        Entry::find('4')->set('author', null)->save();

        $this->assertSame([], Entry::find('1')->get('books', []));
        $this->assertSame([], Entry::find('2')->get('books', []));
    }

    public function test_many_to_one_with_numeric_ids()
    {
        Relate::clear()
            ->oneToMany('books.author', 'authors.books');

        Entry::find('3')->set('author', '1')->save();
        Entry::find('4')->set('author', '1')->save();
        Entry::find('5')->set('author', '1')->save();

        Entry::find('1')->set('books', ['3', '4'])->save();

        $this->assertSame('1', Entry::find('3')->get('author'));
        $this->assertSame('1', Entry::find('4')->get('author'));
        $this->assertNull(Entry::find('5')->get('author'));
    }

    public function test_many_to_many_with_numeric_ids()
    {
        Relate::clear()
            ->manyToMany('conferences.sponsors', 'sponsors.conferences');

        Entry::find('8')->set('sponsors', ['10', '11'])->save();

        $this->assertSame(['8'], Entry::find('10')->get('conferences', []));
        $this->assertSame(['8'], Entry::find('11')->get('conferences', []));

        Entry::find('9')->set('sponsors', ['10'])->save();

        $this->assertSame(['8', '9'], Entry::find('10')->get('conferences', []));

        Entry::find('8')->set('sponsors', ['11'])->save();

        $this->assertSame(['9'], Entry::find('10')->get('conferences', []));
        $this->assertSame(['8'], Entry::find('11')->get('conferences', []));
    }

    public function test_one_to_many_dependent_update_with_numeric_ids()
    {
        Relate::clear()
            ->oneToMany('books.author', 'authors.books');

        Entry::find('3')->set('author', '1')->save();
        Entry::find('4')->set('author', '1')->save();
        Entry::find('5')->set('author', '1')->save();

        Entry::find('6')->set('author', '1')->save();
        Entry::find('7')->set('author', '2')->save();

        Entry::find('1')->set('books', [
            '3', '4', '5',
        ])->save();

        Entry::find('2')->set('books', [
            '6', '7',
        ])->save();

        // Move book '6' from author '2' to author '1'
        Entry::find('1')->set('books', [
            '3', '4', '5', '6',
        ])->save();

        $this->assertSame(['7'], Entry::find('2')->get('books'));

        $this->assertSame([
            '3', '4', '5', '6',
        ], Entry::find('1')->get('books'));

        $this->assertSame('1', Entry::find('3')->get('author'));
        $this->assertSame('1', Entry::find('4')->get('author'));
        $this->assertSame('1', Entry::find('5')->get('author'));
        $this->assertSame('1', Entry::find('6')->get('author'));

        $this->assertSame('2', Entry::find('7')->get('author'));
    }

    public function test_one_to_many_removal_with_numeric_ids()
    {
        Relate::clear()
            ->oneToMany('books.author', 'authors.books');

        Entry::find('3')->set('author', '1')->save();
        Entry::find('4')->set('author', '1')->save();

        $this->assertSame(['3', '4'], Entry::find('1')->get('books', []));

        Entry::find('3')->set('author', null)->save();

        $this->assertSame(['4'], Entry::find('1')->get('books', []));
        $this->assertNull(Entry::find('3')->get('author'));
    }
}
