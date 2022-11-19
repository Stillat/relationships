<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\Facades\User;
use Stillat\Relationships\Listeners\TermSavingListener;
use Stillat\Relationships\Support\Facades\Relate;

class OneToOneTest extends RelationshipTestCase
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

        TermSavingListener::$break = true;
        $this->getTerm('topics-one')->set('single_post', null)->save();

        $this->assertNull(Entry::find('articles-1')->get('post_topic', null));
    }
}
