<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Stillat\Relationships\Support\Facades\Relate;

class ManyToManyTest extends RelationshipTestCase
{
    public function test_many_to_many_relationship()
    {
        Relate::clear()
            ->manyToMany('conferences.sponsors', 'sponsors.sponsoring');

        Entry::find('sponsors-1')->set('sponsoring', [
            'conferences-1',
            'conferences-2',
        ])->save();

        Entry::find('sponsors-2')->set('sponsoring', [
            'conferences-2',
        ])->save();

        $this->assertSame(['sponsors-1', 'sponsors-2'], Entry::find('conferences-2')->get('sponsors'));
        $this->assertSame(['sponsors-1'], Entry::find('conferences-1')->get('sponsors'));

        Entry::find('conferences-1')->set('sponsors', ['sponsors-2'])->save();

        $this->assertSame(['conferences-2'], Entry::find('sponsors-1')->get('sponsoring'));
        $this->assertSame(['conferences-2', 'conferences-1'], Entry::find('sponsors-2')->get('sponsoring'));

        Entry::find('conferences-1')->set('sponsors', [])->save();
        Entry::find('conferences-2')->set('sponsors', [])->save();

        $this->assertSame([], Entry::find('sponsors-1')->get('sponsoring', []));
        $this->assertSame([], Entry::find('sponsors-2')->get('sponsoring', []));
    }

    public function test_many_to_many_relationship_with_events()
    {
        Relate::clear()
            ->manyToMany('conferences.sponsors', 'sponsors.sponsoring')
            ->withEvents();

        Entry::find('sponsors-1')->set('sponsoring', [
            'conferences-1',
            'conferences-2',
        ])->save();

        Entry::find('sponsors-2')->set('sponsoring', [
            'conferences-2',
        ])->save();

        $this->assertSame(['sponsors-1', 'sponsors-2'], Entry::find('conferences-2')->get('sponsors'));
        $this->assertSame(['sponsors-1'], Entry::find('conferences-1')->get('sponsors'));

        Entry::find('conferences-1')->set('sponsors', ['sponsors-2'])->save();

        $this->assertSame(['conferences-2'], Entry::find('sponsors-1')->get('sponsoring'));
        $this->assertSame(['conferences-2', 'conferences-1'], Entry::find('sponsors-2')->get('sponsoring'));

        Entry::find('conferences-1')->set('sponsors', [])->save();
        Entry::find('conferences-2')->set('sponsors', [])->save();

        $this->assertSame([], Entry::find('sponsors-1')->get('sponsoring', []));
        $this->assertSame([], Entry::find('sponsors-2')->get('sponsoring', []));
    }

    public function test_many_to_many_user_relationships()
    {
        Relate::clear()
            ->manyToMany('conferences.conference_users', 'user:user_conferences');

        User::find('user-1')->set('user_conferences', [
            'conferences-1',
            'conferences-2',
        ])->save();

        $this->assertSame(['user-1'], Entry::find('conferences-1')->get('conference_users', []));
        $this->assertSame(['user-1'], Entry::find('conferences-2')->get('conference_users', []));

        Entry::find('conferences-1')->set('conference_users', ['user-2'])->save();

        $this->assertSame(['user-2'], Entry::find('conferences-1')->get('conference_users', []));
        $this->assertSame(['conferences-2'], User::find('user-1')->get('user_conferences', []));
        $this->assertSame(['conferences-1'], User::find('user-2')->get('user_conferences', []));
    }

    public function test_many_to_many_term_relationships()
    {
        Relate::clear()
            ->manyToMany('term:topics.posts', 'entry:articles.topics');

        Entry::find('articles-1')->set('topics', [
            'topics-one',
            'topics-two',
        ])->save();

        $this->assertSame(['articles-1'], $this->getTerm('topics-one')->get('posts', []));
        $this->assertSame(['articles-1'], $this->getTerm('topics-two')->get('posts', []));

        Entry::find('articles-1')->set('topics', ['topics-two'])->save();

        $this->assertSame(['articles-1'], $this->getTerm('topics-two')->get('posts', []));

        Entry::find('articles-1')->set('topics', [])->save();

        $this->assertSame([], $this->getTerm('topics-one')->get('posts', []));
        $this->assertSame([], $this->getTerm('topics-two')->get('posts', []));
    }
}
