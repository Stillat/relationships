<?php

namespace Tests\Eloquent;

use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Stillat\Relationships\Support\Facades\Relate;

class OneToOneTest extends EloquentRelationshipTestCase
{
    public function test_one_to_one_relationship()
    {
        Relate::clear()
            ->oneToOne('employees.position', 'positions.filled_by');

        Entry::find('employees-1')->set('position', 'positions-1')->save();

        $this->assertSame('employees-1', Entry::find('positions-1')->get('filled_by', null));

        Entry::find('positions-1')->set('filled_by', null)->save();

        $this->assertNull(Entry::find('employees-1')->get('position', null));
    }

    public function test_one_to_one_user_relationship()
    {
        Relate::clear()
            ->oneToOne('books.book_author', 'user:book');

        Entry::find('books-1')->set('book_author', 'user-1')->save();

        $this->assertSame('books-1', User::find('user-1')->get('book', null));

        User::find('user-1')->set('book', null)->save();
        $this->assertNull(Entry::find('books-1')->get('book_author', null));
    }

    public function test_one_to_one_term_relationship()
    {
        Relate::clear()
            ->oneToOne('term:topics.single_post', 'entry:articles.post_topic');

        Entry::find('articles-1')->set('post_topic', 'topics-one')->save();

        $this->assertSame('articles-1', $this->getTerm('topics-one')->get('single_post', null));

        $this->getTerm('topics-one')->set('single_post', null)->save();

        $this->assertNull(Entry::find('articles-1')->get('post_topic', null));
    }

    public function test_one_to_one_reassignment()
    {
        Relate::clear()
            ->oneToOne('employees.position', 'positions.filled_by');

        Entry::find('employees-1')->set('position', 'positions-1')->save();

        $this->assertSame('employees-1', Entry::find('positions-1')->get('filled_by', null));

        // Reassign: employees-2 claims positions-1.
        // The processor evicts the old holder (employees-1) before
        // setting the new value to maintain one-to-one integrity.
        Entry::find('employees-2')->set('position', 'positions-1')->save();

        $this->assertSame('employees-2', Entry::find('positions-1')->get('filled_by', null));
        $this->assertNull(Entry::find('employees-1')->get('position', null));
    }

    public function test_one_to_one_reassignment_with_events()
    {
        Relate::clear()
            ->oneToOne('employees.position', 'positions.filled_by')
            ->withEvents();

        Entry::find('employees-1')->set('position', 'positions-1')->save();

        $this->assertSame('employees-1', Entry::find('positions-1')->get('filled_by', null));

        Entry::find('employees-2')->set('position', 'positions-1')->save();

        $this->assertSame('employees-2', Entry::find('positions-1')->get('filled_by', null));
        $this->assertNull(Entry::find('employees-1')->get('position', null));
    }

    public function test_one_to_one_reassignment_from_inverse()
    {
        Relate::clear()
            ->oneToOne('employees.position', 'positions.filled_by');

        Entry::find('employees-1')->set('position', 'positions-1')->save();

        $this->assertSame('employees-1', Entry::find('positions-1')->get('filled_by', null));

        // Reassign from the inverse side: positions-1.filled_by changes to employees-2.
        // The processor sees removed=[employees-1], added=[employees-2].
        Entry::find('positions-1')->set('filled_by', 'employees-2')->save();

        $this->assertSame('employees-2', Entry::find('positions-1')->get('filled_by', null));
        $this->assertSame('positions-1', Entry::find('employees-2')->get('position', null));
        $this->assertNull(Entry::find('employees-1')->get('position', null));
    }
}
