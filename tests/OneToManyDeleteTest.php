<?php

namespace Tests;

use Statamic\Facades\Entry;
use Stillat\Relationships\Support\Facades\Relate;

class OneToManyDeleteTest extends RelationshipTestCase
{
    public function test_one_to_many_delete()
    {
        Relate::clear()
            ->oneToMany('books.author', 'authors.books');

        Entry::find('books-1')->set('author', 'authors-1')->save();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));

        Entry::find('books-2')->set('author', 'authors-1')->save();

        $this->assertSame(['books-1', 'books-2'], Entry::find('authors-1')->get('books', []));

        Entry::find('books-2')->set('author', 'authors-2')->save();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));
        $this->assertSame(['books-2'], Entry::find('authors-2')->get('books', []));

        Entry::find('books-1')->delete();
        Entry::find('books-2')->delete();

        $this->assertSame([], Entry::find('authors-1')->get('books', []));
        $this->assertSame([], Entry::find('authors-2')->get('books', []));
    }
}