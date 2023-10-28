<?php

namespace Stillat\Relationships\Processors\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Statamic\Auth\Eloquent\User;
use Statamic\Contracts\Entries\Entry;

trait GetsFieldValues
{
    protected function getPristineValue($fieldName, $entry, $default = null)
    {
        if ($entry instanceof User || (method_exists($entry, 'model') && method_exists($entry, 'toModel'))) {
            $entry = $entry->model();
        }

        if ($entry instanceof Model) {
            return $entry->getOriginal($fieldName) ?? $default;
        }

        return $this->getFieldValue($fieldName, $entry, $default);
    }

    /**
     * @param  string  $fieldName
     * @param  Entry  $entry
     * @param  mixed|null  $default
     */
    protected function getFieldValue($fieldName, $entry, $default = null)
    {
        if (Str::contains($fieldName, '*')) {
            $data = data_get($entry->data()->all(), $fieldName, $default);
        } else {
            $data = $entry->get($fieldName, $default);
        }

        if (is_array($data)) {
            $data = array_filter($data);
        }

        return $data;
    }
}
