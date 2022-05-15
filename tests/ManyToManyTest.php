<?php

namespace Tests;

use Statamic\Facades\Entry;
use Stillat\Relationships\Support\Facades\Relate;

class ManyToManyTest extends RelationshipTestCase
{
    public function test_many_to_many_relationship()
    {
        Relate::clear()
            ->manyToMany('conferences.sponsors', 'sponsors.sponsoring');

        Entry::find('sponsors-1')->set('sponsoring', [
            'conferences-1',
            'conferences-2'
        ])->save();

        Entry::find('sponsors-2')->set('sponsoring', [
            'conferences-2'
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
}