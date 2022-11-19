<?php

namespace Tests;

use Stillat\Relationships\RelationshipManager;

class SetNotationRelationshipsTest extends RelationshipTestCase
{
    public function test_it_extracts_collection_names()
    {
        $left = 'entry:{events,clubs,businesses,services,articles}.related';

        $this->assertSame([
            'entry:events.related',
            'entry:clubs.related',
            'entry:businesses.related',
            'entry:services.related',
            'entry:articles.related',
        ], RelationshipManager::extractCollections($left));
    }
}
