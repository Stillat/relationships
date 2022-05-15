<?php

namespace Stillat\Relationships;

use Stillat\Relationships\Processors\RelationshipProcessor;

class RelationshipManager
{
    /**
     * @var EntryRelationship[]
     */
    protected $relationships = [];

    /**
     * @var RelationshipProcessor
     */
    protected $processor;

    public function __construct(RelationshipProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * @return RelationshipProcessor
     */
    public function processor()
    {
        return $this->processor;
    }

    /**
     * @param  string  $handle  The collection handle.
     * @return EntryRelationship
     */
    public function collection($handle)
    {
        $relationship = new EntryRelationship();
        $relationship->collection($handle);

        if (! array_key_exists($handle, $this->relationships)) {
            $this->relationships[$handle] = [];
        }

        $this->relationships[$handle][] = $relationship;

        return $relationship;
    }

    private function getRelationship($left, $right)
    {
        return $this->collection($left[0])->field($left[1])->isRelatedTo($right[0])->through($right[1]);
    }

    private function buildRelationships($leftCollectionHandle, $rightCollectionHandle)
    {
        $left = $this->getFieldDetails($leftCollectionHandle);
        $right = $this->getFieldDetails($rightCollectionHandle);

        $leftRelationship = $this->getRelationship($left, $right);
        $rightRelationship = $this->getRelationship($right, $left)->isAutomaticInverse();

        return collect([$leftRelationship, $rightRelationship]);
    }

    /**
     * @param  string  $leftCollectionHandle
     * @param  string  $rightCollectionHandle
     * @return RelationshipProxy
     */
    public function oneToOne($leftCollectionHandle, $rightCollectionHandle)
    {
        return new RelationshipProxy($this->buildRelationships($leftCollectionHandle, $rightCollectionHandle)->each(function (EntryRelationship $relationship) {
            $relationship->oneToOne();
        }));
    }

    /**
     * @param  string  $leftCollectionHandle
     * @param  string  $rightCollectionHandle
     * @return RelationshipProxy
     */
    public function oneToMany($leftCollectionHandle, $rightCollectionHandle)
    {
        $left = $this->getFieldDetails($leftCollectionHandle);
        $right = $this->getFieldDetails($rightCollectionHandle);

        return new RelationshipProxy([
            $this->getRelationship($left, $right)->manyToOne(),
            $this->getRelationship($right, $left)->oneToMany()->isAutomaticInverse(),
        ]);
    }

    /**
     * @param  string  $leftCollectionHandle
     * @param  string  $rightCollectionHandle
     * @return RelationshipProxy
     */
    public function manyToOne($leftCollectionHandle, $rightCollectionHandle)
    {
        $left = $this->getFieldDetails($leftCollectionHandle);
        $right = $this->getFieldDetails($rightCollectionHandle);

        return new RelationshipProxy([
            $this->getRelationship($left, $right)->oneToMany(),
            $this->getRelationship($right, $left)->manyToOne()->isAutomaticInverse(),
        ]);
    }

    /**
     * @param  string  $leftCollectionHandle
     * @param  string  $rightCollectionHandle
     * @return RelationshipProxy
     */
    public function manyToMany($leftCollectionHandle, $rightCollectionHandle)
    {
        return new RelationshipProxy($this->buildRelationships($leftCollectionHandle, $rightCollectionHandle)->each(function (EntryRelationship $relationship) {
            $relationship->manyToMany();
        }));
    }

    protected function getFieldDetails($handle)
    {
        return explode('.', $handle, 2);
    }

    /**
     * Determines if relationships exist for the specified collection.
     *
     * @param  string  $handle  The collection handle.
     * @return bool
     */
    public function hasRelationshipsForCollection($handle)
    {
        return ! empty($this->getRelationshipsForCollection($handle));
    }

    /**
     * Gets all relationships for the specified collection.
     *
     * @param  string  $handle  The collection handle.
     * @return EntryRelationship[]
     */
    public function getRelationshipsForCollection($handle)
    {
        if (! array_key_exists($handle, $this->relationships)) {
            return [];
        }

        return $this->relationships[$handle];
    }

    public function getAll()
    {
        return $this->relationships;
    }

    public function getAllRelationships()
    {
        $relationships = [];

        foreach ($this->relationships as $collectionRelationships) {
            $relationships = array_merge($relationships, $collectionRelationships);
        }

        return collect($relationships)->sortBy('index')->values()->all();
    }

    public function getCollections()
    {
        return array_keys($this->relationships);
    }

    /**
     * Clears all relationships.
     *
     * @return $this
     */
    public function clear()
    {
        $this->relationships = [];

        return $this;
    }
}
