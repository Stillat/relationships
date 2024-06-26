<?php

namespace Stillat\Relationships;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Statamic\Support\Arr;
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

    protected $validEntityTypes = ['entry', 'user', 'term'];

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

    public function term($termName)
    {
        $relationship = new EntryRelationship();
        $relationship->leftType = 'term';
        $relationship->leftField = $termName;
        $relationship->leftCollection = '[term]';
        $relationship->taxonomyName = $termName;

        if (! array_key_exists('terms', $this->relationships)) {
            $this->relationships['terms'] = [];
        }

        $this->relationships['terms'][] = $relationship;

        return $relationship;
    }

    protected function getRelationshipBuilder($left, $leftType)
    {
        if ($leftType == 'entry') {
            return $this->collection($left);
        } elseif ($leftType == 'user') {
            return $this->user($left);
        } elseif ($leftType == 'term') {
            return $this->term($left);
        }
    }

    private function getRelationship($left, $right)
    {
        if (! in_array($left[0], $this->validEntityTypes)) {
            throw new InvalidArgumentException($left[0].' is not a valid entity type.');
        }

        if (! in_array($right[0], $this->validEntityTypes)) {
            throw new InvalidArgumentException($right[0].' is not a valid entity type.');
        }

        return $this->getRelationshipBuilder($left[1], $left[0])
            ->field($left[2], $left[0])
            ->isRelatedTo($right[1])
            ->through($right[2], $right[0]);
    }

    private function buildOneToOneRelationships($relationships)
    {
        $builtRelationships = [];

        foreach ($relationships as $relationship) {
            $left = $this->getFieldDetails($relationship[0]);
            $right = $this->getFieldDetails($relationship[1]);

            $builtRelationships[] = $this->getRelationship($left, $right)->oneToOne();
            $builtRelationships[] = $this->getRelationship($right, $left)
                ->oneToOne()
                ->isAutomaticInverse()
                ->withOriginRelationship($builtRelationships[0]);
        }

        return new RelationshipProxy($builtRelationships);
    }

    /**
     * @param  string  $leftCollectionHandle
     * @param  string  $rightCollectionHandle
     * @return RelationshipProxy
     */
    public function oneToOne($leftCollectionHandle, $rightCollectionHandle)
    {
        return $this->buildOneToOneRelationships($this->getRelationshipItems($leftCollectionHandle, $rightCollectionHandle));
    }

    private function buildOneToManyRelationships($relationships)
    {
        $builtRelationships = [];

        foreach ($relationships as $relationship) {
            $left = $this->getFieldDetails($relationship[0]);
            $right = $this->getFieldDetails($relationship[1]);

            $builtRelationships[] = $this->getRelationship($left, $right)->manyToOne();
            $builtRelationships[] = $this->getRelationship($right, $left)
                ->oneToMany()
                ->isAutomaticInverse()
                ->withOriginRelationship($builtRelationships[0]);
        }

        return new RelationshipProxy($builtRelationships);
    }

    /**
     * @param  string  $leftCollectionHandle
     * @param  string  $rightCollectionHandle
     * @return RelationshipProxy
     */
    public function oneToMany($leftCollectionHandle, $rightCollectionHandle)
    {
        return $this->buildOneToManyRelationships($this->getRelationshipItems($leftCollectionHandle, $rightCollectionHandle));
    }

    private function buildManyToOneRelationships($relationships)
    {
        $builtRelationships = [];

        foreach ($relationships as $relationship) {
            $left = $this->getFieldDetails($relationship[0]);
            $right = $this->getFieldDetails($relationship[1]);

            $builtRelationships[] = $this->getRelationship($left, $right)->oneToOne();
            $builtRelationships[] = $this->getRelationship($right, $left)
                ->manyToOne()
                ->isAutomaticInverse()
                ->withOriginRelationship($builtRelationships[0]);
        }

        return new RelationshipProxy($builtRelationships);
    }

    /**
     * @param  string  $leftCollectionHandle
     * @param  string  $rightCollectionHandle
     * @return RelationshipProxy
     */
    public function manyToOne($leftCollectionHandle, $rightCollectionHandle)
    {
        return $this->buildManyToOneRelationships($this->getRelationshipItems($leftCollectionHandle, $rightCollectionHandle));
    }

    /**
     * Extracts collection and field information from relationship set notation.
     *
     * @param  string  $handles
     * @return string[]
     */
    public static function extractCollections($handles)
    {
        if (! Str::contains($handles, '{')) {
            return [$handles];
        }

        $parts = explode(':', $handles, 2);
        $type = $parts[0];
        $parts = explode('.', $parts[1], 2);
        $field = $parts[1];

        if (ctype_punct(mb_substr($parts[0], 0, 1))) {
            $parts[0] = Str::substr($parts[0], 1, -1);
        }

        $handles = collect(explode(',', $parts[0]));

        return $handles->map(function ($handle) use ($type, $field) {
            return $type.':'.$handle.'.'.$field;
        })->all();
    }

    private function getRelationshipItems($leftCollectionHandle, $rightCollectionHandle)
    {
        $relationships = [];

        if (Str::contains($leftCollectionHandle, '{') || Str::contains($rightCollectionHandle, '{')) {
            $left = self::extractCollections($leftCollectionHandle);
            $right = self::extractCollections($rightCollectionHandle);
            $inverted[] = [];
            $relationships = collect(Arr::crossJoin($left, $right))->filter(function ($pair) use (&$inverted) {
                $normal = $pair[0].':'.$pair[1];

                if (in_array($normal, $inverted)) {
                    return false;
                }

                $inverted[] = $pair[1].':'.$pair[0];

                return true;
            })->all();
        } else {
            $relationships[] = [$leftCollectionHandle, $rightCollectionHandle];
        }

        return $relationships;
    }

    private function buildManyToManyRelationships($relationships)
    {
        $builtRelationships = [];

        foreach ($relationships as $relationship) {
            $left = $this->getFieldDetails($relationship[0]);
            $right = $this->getFieldDetails($relationship[1]);

            $builtRelationships[] = $this->getRelationship($left, $right)->manyToMany();
            $builtRelationships[] = $this->getRelationship($right, $left)
                ->manyToMany()
                ->isAutomaticInverse()
                ->withOriginRelationship($builtRelationships[0]);
        }

        return new RelationshipProxy($builtRelationships);
    }

    /**
     * @param  string  $leftCollectionHandle
     * @param  string  $rightCollectionHandle
     * @return RelationshipProxy
     */
    public function manyToMany($leftCollectionHandle, $rightCollectionHandle)
    {
        return $this->buildManyToManyRelationships($this->getRelationshipItems($leftCollectionHandle, $rightCollectionHandle));
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

    public function hasTermRelationships()
    {
        if (! array_key_exists('terms', $this->relationships)) {
            return false;
        }

        return count($this->relationships['terms']) > 0;
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
     * @param  string  $entityType  The entity type.
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

    public function getAllTermRelationships()
    {
        return $this->getEntityTypeRelationships('terms');
    }

    public function getTermRelationshipsFor($term)
    {
        return collect($this->getAllTermRelationships())->filter(function (EntryRelationship $relationship) use ($term) {
            return $relationship->leftType == 'term' && $relationship->taxonomyName == $term;
        })->all();
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

        foreach ($this->getAllTermRelationships() as $termRelationship) {
            $relationships[] = $termRelationship;
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
