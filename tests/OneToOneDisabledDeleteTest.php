<?php

namespace Tests;

use Statamic\Facades\Entry;
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
}