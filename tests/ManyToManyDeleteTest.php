<?php

namespace Tests;

use Statamic\Facades\Entry;
use Stillat\Relationships\Support\Facades\Relate;

class ManyToManyDeleteTest extends RelationshipTestCase
{
    public function test_many_to_many_delete()
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

        Entry::find('conferences-1')->delete();

        $this->assertSame(['conferences-2'], Entry::find('sponsors-1')->get('sponsoring'));
        $this->assertSame(['conferences-2'], Entry::find('sponsors-2')->get('sponsoring'));

        Entry::find('conferences-2')->delete();

        $this->assertSame([], Entry::find('sponsors-1')->get('sponsoring', []));
        $this->assertSame([], Entry::find('sponsors-2')->get('sponsoring', []));
    }
}
