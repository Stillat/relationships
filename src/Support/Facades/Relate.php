<?php

namespace Stillat\Relationships\Support\Facades;

use Illuminate\Support\Facades\Facade;
use Stillat\Relationships\EntryRelationship;
use Stillat\Relationships\RelationshipManager;
use Stillat\Relationships\RelationshipProxy;

/**
 * @method static RelationshipProxy manyToMany(string $leftCollectionHandle, string $rightCollectionHandle)
 * @method static RelationshipProxy oneToOne(string $leftCollectionHandle, string $rightCollectionHandle)
 * @method static RelationshipProxy oneToMany(string $leftCollectionHandle, string $rightCollectionHandle)
 * @method static RelationshipProxy manyToOne(string $leftCollectionHandle, string $rightCollectionHandle)
 * @method static RelationshipManager clear()
 * @method static EntryRelationship collection(string $handle)
 */
class Relate extends Facade
{
    protected static function getFacadeAccessor()
    {
        return RelationshipManager::class;
    }
}
