<?php

namespace Stillat\Relationships\Processors\Concerns;

use Illuminate\Support\Str;
use Statamic\Contracts\Entries\Entry;

trait GetsFieldValues
{
    /**
     * @param string $fieldName
     * @param Entry $entry
     * @param mixed|null $default
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
