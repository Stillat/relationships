<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Stillat\Relationships\Support\Facades\Relate;

class OneToManyDisabledDeleteTest extends RelationshipTestCase
{
    public function test_one_to_many_delete_disabled()
    {
        Relate::clear()
            ->oneToMany('books.author', 'authors.books')
            ->allowDelete(false);

        Entry::find('books-1')->set('author', 'authors-1')->save();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));

        Entry::find('books-2')->set('author', 'authors-1')->save();

        $this->assertSame(['books-1', 'books-2'], Entry::find('authors-1')->get('books', []));

        Entry::find('books-2')->set('author', 'authors-2')->save();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));
        $this->assertSame(['books-2'], Entry::find('authors-2')->get('books', []));

        Entry::find('books-1')->delete();
        Entry::find('books-2')->delete();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));
        $this->assertSame(['books-2'], Entry::find('authors-2')->get('books', []));
    }

    public function test_one_to_many_user_delete_disabled()
    {
        Relate::clear()
            ->oneToMany('conferences.managed_by', 'user:managing_conferences')
            ->allowDelete(false);

        Entry::find('conferences-1')->set('managed_by', 'user-1')->save();
        Entry::find('conferences-2')->set('managed_by', 'user-1')->save();

        $this->assertSame(['conferences-1', 'conferences-2'], User::find('user-1')->get('managing_conferences', []));
        Entry::find('conferences-1')->delete();
        $this->assertSame(['conferences-1', 'conferences-2'], User::find('user-1')->get('managing_conferences', []));

        Entry::find('conferences-2')->delete();
        $this->assertSame(['conferences-1', 'conferences-2'], User::find('user-1')->get('managing_conferences', []));
    }
}
