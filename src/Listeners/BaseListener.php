<?php

namespace Stillat\Relationships\Listeners;

use Statamic\Auth\Eloquent\User;

abstract class BaseListener
{
    protected function checkForDatabaseObject($object)
    {
        if ($object instanceof User || (method_exists($object, 'model') && method_exists($object, 'toModel'))) {
            // If model() returns null (eg. on create), fallback to $object, as `clone null` will throw an exception
            $object = $object->model() ?: $object;
        }

        return clone $object;
    }
}
