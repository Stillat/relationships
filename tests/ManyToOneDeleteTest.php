<?php

namespace Tests;

use Statamic\Facades\Entry;
use Stillat\Relationships\Support\Facades\Relate;

class ManyToOneDeleteTest extends  RelationshipTestCase
{
    public function test_many_to_one_delete()
    {
        Relate::clear()
            ->manyToOne( 'authors.books', 'books.author');

        Entry::find('books-1')->set('author', 'authors-1')->save();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));

        Entry::find('books-2')->set('author', 'authors-1')->save();

        $this->assertSame(['books-1', 'books-2'], Entry::find('authors-1')->get('books', []));

        Entry::find('books-2')->set('author', 'authors-2')->save();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));
        $this->assertSame(['books-2'], Entry::find('authors-2')->get('books', []));

        Entry::find('authors-1')->delete();
        Entry::find('authors-2')->delete();

        $this->assertNull(Entry::find('books-1')->get('author'));
        $this->assertNull(Entry::find('books-2')->get('author'));
    }
}