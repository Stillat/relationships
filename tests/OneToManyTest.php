<?php

namespace Tests;

use Statamic\Facades\Entry;
use Statamic\Facades\User;
use Stillat\Relationships\Support\Facades\Relate;

class OneToManyTest extends RelationshipTestCase
{
    public function test_one_to_many_relationships()
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

        Entry::find('books-1')->set('author', null)->save();
        Entry::find('books-2')->set('author', null)->save();

        $this->assertSame([], Entry::find('authors-1')->get('books', []));
        $this->assertSame([], Entry::find('authors-2')->get('books', []));
    }

    public function test_explicit_one_to_many()
    {
        Relate::clear();
        Relate::collection('books')
                ->field('author')
            ->isRelatedTo('authors')
                ->through('books')
            ->manyToOne();

        Relate::collection('authors')
                ->field('books')
            ->isRelatedTo('books')
                ->through('author')
            ->oneToMany();

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

    public function test_one_to_many_user_relationships()
    {
        Relate::clear()
            ->oneToMany('conferences.managed_by', 'user:managing_conferences');

        Entry::find('conferences-1')->set('managed_by', 'user-1')->save();

        $this->assertSame(['conferences-1'], User::find('user-1')->get('managing_conferences', []));

        Entry::find('conferences-2')->set('managed_by', 'user-1')->save();

        $this->assertSame(['conferences-1', 'conferences-2'], User::find('user-1')->get('managing_conferences', []));

        Entry::find('conferences-2')->set('managed_by', 'user-2')->save();

        $this->assertSame(['conferences-1'], User::find('user-1')->get('managing_conferences', []));
        $this->assertSame(['conferences-2'], User::find('user-2')->get('managing_conferences', []));

        Entry::find('conferences-1')->set('managed_by', null)->save();
        Entry::find('conferences-2')->set('managed_by', null)->save();

        $this->assertSame([], User::find('user-1')->get('managing_conferences', []));
        $this->assertSame([], User::find('user-2')->get('managing_conferences', []));
    }

    public function test_one_to_many_term_relationships()
    {
        Relate::clear()
            ->oneToMany('entry:articles.post_topic', 'term:topics.posts');

        Entry::find('articles-1')->set('post_topic', 'topics-one')->save();

        $this->assertSame(['articles-1'], $this->getTerm('topics-one')->get('posts', []));

        Entry::find('articles-2')->set('post_topic', 'topics-one')->save();

        $this->assertSame(['articles-1', 'articles-2'], $this->getTerm('topics-one')->get('posts', []));

        Entry::find('articles-2')->set('post_topic', 'topics-two')->save();

        $this->assertSame(['articles-1'], $this->getTerm('topics-one')->get('posts', []));
        $this->assertSame(['articles-2'], $this->getTerm('topics-two')->get('posts', []));

        Entry::find('articles-1')->set('post_topic', null)->save();
        Entry::find('articles-2')->set('post_topic', null)->save();

        $this->assertSame([], $this->getTerm('topics-one')->get('posts', []));
        $this->assertSame([], $this->getTerm('topics-two')->get('posts', []));
    }
}
