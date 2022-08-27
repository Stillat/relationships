<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Stillat\Relationships\Support\Facades\Relate;

class OneToOneDisabledDeleteTest extends RelationshipTestCase
{
    public function test_one_to_one_delete_disabled()
    {
        Relate::clear()
            ->oneToOne('employees.position', 'positions.filled_by')
            ->allowDelete(false);

        Entry::find('employees-1')->set('position', 'positions-1')->save();

        $this->assertSame('employees-1', Entry::find('positions-1')->get('filled_by', null));

        Entry::find('positions-1')->delete();

        $this->assertSame('positions-1', Entry::find('employees-1')->get('position', null));
    }

    public function test_one_to_one_user_disabled_delete()
    {
        Relate::clear()
            ->oneToOne('books.book_author', 'user:book')
            ->allowDelete(false);

        Entry::find('books-1')->set('book_author', 'user-1')->save();

        $this->assertSame('books-1', User::find('user-1')->get('book', null));

        User::find('user-1')->delete();

        $this->assertSame('user-1', Entry::find('books-1')->get('book_author', null));
    }
}
