<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Stillat\Relationships\Support\Facades\Relate;

class ManyToOneDisabledDeleteTest extends RelationshipTestCase
{
    public function test_many_to_one_delete_disabled()
    {
        Relate::clear()
            ->manyToOne('authors.books', 'books.author')
            ->allowDelete(false);

        Entry::find('books-1')->set('author', 'authors-1')->save();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));

        Entry::find('books-2')->set('author', 'authors-1')->save();

        $this->assertSame(['books-1', 'books-2'], Entry::find('authors-1')->get('books', []));

        Entry::find('books-2')->set('author', 'authors-2')->save();

        $this->assertSame(['books-1'], Entry::find('authors-1')->get('books', []));
        $this->assertSame(['books-2'], Entry::find('authors-2')->get('books', []));

        Entry::find('authors-1')->delete();
        Entry::find('authors-2')->delete();

        $this->assertSame('authors-1', Entry::find('books-1')->get('author'));
        $this->assertSame('authors-2', Entry::find('books-2')->get('author'));
    }

    public function test_many_to_one_user_delete_disabled()
    {
        Relate::clear()
            ->manyToOne('user:managing_conferences', 'conferences.managed_by')
            ->allowDelete(false);

        Entry::find('conferences-1')->set('managed_by', 'user-1')->save();

        $this->assertSame(['conferences-1'], User::find('user-1')->get('managing_conferences', []));

        Entry::find('conferences-2')->set('managed_by', 'user-1')->save();

        $this->assertSame(['conferences-1', 'conferences-2'], User::find('user-1')->get('managing_conferences', []));

        Entry::find('conferences-2')->set('managed_by', 'user-2')->save();

        $this->assertSame(['conferences-1'], User::find('user-1')->get('managing_conferences', []));
        $this->assertSame(['conferences-2'], User::find('user-2')->get('managing_conferences', []));

        User::find('user-1')->delete();
        User::find('user-2')->delete();

        $this->assertSame('user-1', Entry::find('conferences-1')->get('managed_by'));
        $this->assertSame('user-2', Entry::find('conferences-2')->get('managed_by'));
    }
}
