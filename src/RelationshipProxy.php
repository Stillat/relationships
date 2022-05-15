<?php

namespace Stillat\Relationships;

use Illuminate\Support\Collection;

class RelationshipProxy
{
    /**
     * @var Collection
     */
    protected $relationships = [];

    public function __construct($relationships)
    {
        if (! $relationships instanceof Collection) {
            $relationships = collect($relationships);
        }

        $this->relationships = $relationships;
    }

    /**
     * Sets whether affected entries will be updated when deleting related entries.
     *
     * @param  bool  $allowDelete  Whether to allow deletes.
     * @return $this
     */
    public function allowDelete($allowDelete = true)
    {
        $this->relationships->each(function (EntryRelationship $relationship) use ($allowDelete) {
            $relationship->allowDeletes($allowDelete);
        });

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
        $this->relationships->each(function (EntryRelationship $relationship) use ($withEvents) {
            $relationship->withEvents($withEvents);
        });

        return $this;
    }
}
