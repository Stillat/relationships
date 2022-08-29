<?php

namespace Stillat\Relationships;

class EntryRelationship
{
    protected static $relationshipCount = 0;

    const TYPE_MANY_TO_MANY = 1;
    const TYPE_ONE_TO_ONE = 2;
    const TYPE_ONE_TO_MANY = 3;
    const TYPE_MANY_TO_ONE = 4;

    public $leftType = '';
    public $rightType = '';

    public $index = 0;
    public $type = self::TYPE_MANY_TO_MANY;
    public $leftCollection = '';
    public $rightCollection = '';
    public $leftField = '';
    public $rightField = '';

    public $inverseIndex = null;
    public $isAutomaticInverse = false;

    public $withEvents = false;
    public $allowDelete = true;

    public function __construct()
    {
        self::$relationshipCount += 1;
        $this->index = self::$relationshipCount;
    }

    public function collection($collection)
    {
        $this->leftCollection = $collection;
    }

    public function field($handle, $entityType = 'entry')
    {
        $this->leftField = $handle;
        $this->leftType = $entityType;

        return $this;
    }

    public function isRelatedTo($rightCollectionHandle)
    {
        $this->rightCollection = $rightCollectionHandle;

        return $this;
    }

    public function through($rightCollectionFieldHandle, $entityType = 'entry')
    {
        $this->rightField = $rightCollectionFieldHandle;
        $this->rightType = $entityType;

        return $this;
    }

    public function manyToMany()
    {
        $this->type = self::TYPE_MANY_TO_MANY;

        return $this;
    }

    public function oneToOne()
    {
        $this->type = self::TYPE_ONE_TO_ONE;

        return $this;
    }

    public function oneToMany()
    {
        $this->type = self::TYPE_ONE_TO_MANY;

        return $this;
    }

    public function manyToOne()
    {
        $this->type = self::TYPE_MANY_TO_ONE;

        return $this;
    }

    /**
     * Sets whether affected entries will be saved quietly.
     *
     * @param  bool  $withEvents  Whether to trigger events.
     * @return $this
     */
    public function withEvents($withEvents = true)
    {
        $this->withEvents = $withEvents;

        return $this;
    }

    public function isAutomaticInverse($isInverse = true, $inverseIndex = null)
    {
        $this->isAutomaticInverse = $isInverse;

        if ($inverseIndex != null) {
            $this->inverseIndex = $inverseIndex;
        } else {
            $this->inverseIndex = $this->index - 1;
        }

        return $this;
    }

    /**
     * Sets whether affected entries will be updated when deleting related entries.
     *
     * @param  bool  $allowDelete  Whether to allow deletes.
     * @return $this
     */
    public function allowDeletes($allowDelete = true)
    {
        $this->allowDelete = $allowDelete;

        return $this;
    }

    public function getRelationshipDescription()
    {
        if ($this->type == EntryRelationship::TYPE_MANY_TO_ONE) {
            return 'Many to One (*-1)';
        } elseif ($this->type == EntryRelationship::TYPE_ONE_TO_MANY) {
            return 'One to Many (1-*)';
        } elseif ($this->type == EntryRelationship::TYPE_MANY_TO_MANY) {
            return 'Many to Many (*-*)';
        } elseif ($this->type == EntryRelationship::TYPE_ONE_TO_ONE) {
            return 'One to One (1-1)';
        }

        return '';
    }

    public function getSymbolDescription()
    {
        if ($this->type == EntryRelationship::TYPE_MANY_TO_ONE) {
            return '(*-1)';
        } elseif ($this->type == EntryRelationship::TYPE_ONE_TO_MANY) {
            return '(1-*)';
        } elseif ($this->type == EntryRelationship::TYPE_MANY_TO_MANY) {
            return '(*-*)';
        } elseif ($this->type == EntryRelationship::TYPE_ONE_TO_ONE) {
            return '(1-1)';
        }

        return '';
    }

    public function getDescription()
    {
        return "{$this->getSymbolDescription()} [{$this->leftCollection}.{$this->leftField} {$this->rightCollection}.{$this->rightField}]";
    }
}
