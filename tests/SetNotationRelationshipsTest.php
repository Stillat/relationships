<?php

namespace Tests;

use Stillat\Relationships\EntryRelationship;
use Stillat\Relationships\RelationshipManager;
use Stillat\Relationships\Support\Facades\Relate;

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
    public function test_it_extracts_collection_names_without_sets()
    {
        Relate::clear()->manyToMany(
            'term:categories.products',
            'entry:{pens,markers}.categories'
        );

        /** @var EntryRelationship[] $relationships */
        $relationships = Relate::getAllRelationships();

        $this->assertCount(4, $relationships);

        $this->assertRelationshipDetails($relationships[0], 'term', 'entry', '[term]', 'pens', 'products', 'categories');
        $this->assertRelationshipDetails($relationships[1], 'entry', 'term', 'pens', 'categories', 'categories', 'products');
        $this->assertRelationshipDetails($relationships[2], 'term', 'entry', '[term]', 'markers', 'products', 'categories');
        $this->assertRelationshipDetails($relationships[3], 'entry', 'term', 'markers', 'categories', 'categories', 'products');
    }

    protected function assertRelationshipDetails(EntryRelationship $relationship, $leftType, $rightType, $leftCollection, $rightCollection, $leftField, $rightField)
    {
        $this->assertSame($leftType, $relationship->leftType);
        $this->assertSame($rightType, $relationship->rightType);
        $this->assertSame($leftCollection, $relationship->leftCollection);
        $this->assertSame($rightCollection, $relationship->rightCollection);
        $this->assertSame($leftField, $relationship->leftField);
        $this->assertSame($rightField, $relationship->rightField);
    }
}
