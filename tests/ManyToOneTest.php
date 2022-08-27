<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Stillat\Relationships\Support\Facades\Relate;

class ManyToOneTest extends RelationshipTestCase
{
    public function test_many_to_one_relationship()
    {
        Relate::clear()
            ->manyToOne('authors.books', 'books.author');

        Entry::find('books-1')->set('author', 'authors-1')->save();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));

        Entry::find('books-2')->set('author', 'authors-1')->save();

        $this->assertSame(['books-1', 'books-2'], Entry::find('authors-1')->get('books', []));

        Entry::find('books-2')->set('author', 'authors-2')->save();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));
        $this->assertSame(['books-2'], Entry::find('authors-2')->get('books', []));

        Entry::find('books-1')->set('author', null)->save();
        Entry::find('books-2')->set('author', null)->save();

        $this->assertSame([], Entry::find('authors-1')->get('books', []));
        $this->assertSame([], Entry::find('authors-2')->get('books', []));
    }

    public function test_many_to_one_user_relationship()
    {
        Relate::clear()
            ->manyToOne('user:managing_conferences', 'conferences.managed_by');

        Entry::find('conferences-1')->set('managed_by', 'user-1')->save();

        $this->assertSame(['conferences-1'], User::find('user-1')->get('managing_conferences', []));

        Entry::find('conferences-2')->set('managed_by', 'user-1')->save();

        $this->assertSame(['conferences-1', 'conferences-2'], User::find('user-1')->get('managing_conferences', []));

        Entry::find('conferences-2')->set('managed_by', 'user-2')->save();

        $this->assertSame(['conferences-1'], User::find('user-1')->get('managing_conferences', []));
        $this->assertSame(['conferences-2'], User::find('user-2')->get('managing_conferences', []));
    }
}
