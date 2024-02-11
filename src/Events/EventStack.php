<?php

namespace Stillat\Relationships\Events;

class EventStack
{
    protected $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }

    public function count()
    {
        return $this->count;
    }
}
