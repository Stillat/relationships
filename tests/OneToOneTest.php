<?php

namespace Tests;

use Statamic\Facades\Entry;
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
}
