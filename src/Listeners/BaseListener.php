<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Auth\Eloquent\User;

abstract class BaseListener
{
    protected function checkForDatabaseObject($object)
    {
        if ($object instanceof User || (method_exists($object, 'model') && method_exists($object, 'toModel'))) {
            $object = $object->model();
        }

        return clone $object;
    }
}