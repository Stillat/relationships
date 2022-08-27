<?php

namespace Stillat\Relationships;

use InvalidArgumentException;
use Illuminate\Support\Str;
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

    protected $validEntityTypes = ['entry', 'user'];

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

        if (! array_key_exists('entries', $this->relationships)) {
            $this->relationships['entries'] = [];
        }

        if (! array_key_exists($handle, $this->relationships['entries'])) {
            $this->relationships['entries'][$handle] = [];
        }

        $this->relationships['entries'][$handle][] = $relationship;

        return $relationship;
    }

    public function user($fieldName)
    {
        $relationship = new EntryRelationship();
        $relationship->leftType = 'user';
        $relationship->leftField = $fieldName;
        $relationship->leftCollection = '[user]';

        if (! array_key_exists('users', $this->relationships)) {
            $this->relationships['users'] = [];
        }

        $this->relationships['users'][] = $relationship;

        return $relationship;
    }

    protected function getRelationshipBuilder($left, $leftType)
    {
        if ($leftType == 'entry') {
            return $this->collection($left);
        } else if ($leftType == 'user') {
            return $this->user($left);
        }
    }

    private function getRelationship($left, $right)
    {
        if (! in_array($left[0], $this->validEntityTypes)) {
            throw new InvalidArgumentException($left[0]. ' is not a valid entity type.');
        }

        if (! in_array($right[0], $this->validEntityTypes)) {
            throw new InvalidArgumentException($right[0]. ' is not a valid entity type.');
        }

        return $this->getRelationshipBuilder($left[1], $left[0])
                ->field($left[2], $left[0])
            ->isRelatedTo($right[1])
                ->through($right[2], $right[0]);
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
        $details = explode('.', $handle, 2);

        if (Str::contains($details[0], ':')) {
            $typeDetails = array_shift($details);
            $additionalDetails = explode(':', $typeDetails, 2);

            if ($additionalDetails[0] == 'user') {
                array_unshift($additionalDetails, 'user');
            }

            array_unshift($details, ...$additionalDetails);
        } else {
            array_unshift($details, 'entry');
        }

        return $details;
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

    public function hasUserRelationships()
    {
        if (! array_key_exists('users', $this->relationships)) {
            return false;
        }

        return count($this->relationships['users']) > 0;
    }

    /**
     * Gets all relationships for the specified collection.
     *
     * @param  string  $handle  The collection handle.
     * @return EntryRelationship[]
     */
    public function getRelationshipsForCollection($handle)
    {
        if (! array_key_exists('entries', $this->relationships)) {
            return [];
        }

        if (! array_key_exists($handle, $this->relationships['entries'])) {
            return [];
        }

        return $this->relationships['entries'][$handle];
    }

    public function getAll()
    {
        return $this->getAllRelationships();
    }

    /**
     * Returns all relationships for the provided entity type.
     *
     * @param string $entityType The entity type.
     * @return array|EntryRelationship
     */
    private function getEntityTypeRelationships($entityType)
    {
        if (! array_key_exists($entityType, $this->relationships)) {
            return [];
        }

        return $this->relationships[$entityType];
    }

    /**
     * Returns all entry relationships.
     *
     * @return array|EntryRelationship
     */
    public function getAllEntryRelationships()
    {
        return $this->getEntityTypeRelationships('entries');
    }

    /**
     * Returns all user relationships.
     *
     * @return array|EntryRelationship
     */
    public function getAllUserRelationships()
    {
        return $this->getEntityTypeRelationships('users');
    }

    public function getAllRelationships()
    {
        $relationships = [];

        foreach ($this->getAllEntryRelationships() as $collectionRelationships) {
            $relationships = array_merge($relationships, $collectionRelationships);
        }

        foreach ($this->getAllUserRelationships() as $userRelationship) {
            $relationships[] = $userRelationship;
        }

        return collect($relationships)->sortBy('index')->values()->all();
    }

    /**
     * Returns the names of all collections that have relationships.
     *
     * @return string[]
     */
    public function getCollections()
    {
        return array_keys($this->relationships['entries']);
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
