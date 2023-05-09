<?php

namespace Stillat\Relationships\Comparisons;

class ListComparator
{
    /**
     * Gets the differences between two arrays.
     *
     * @param  array|null  $a  The original array data.
     * @param  array|null  $b  The updated array data.
     * @return ComparisonResult
     */
    public static function compare($a, $b)
    {
        if (is_null($a)) {
            $a = [];
        }

        if (is_null($b)) {
            $b = [];
        }

        if (is_string($a)) {
            $a = [$a];
        }

        if (is_string($b)) {
            $b = [$b];
        }

        if (! is_array($a)) {
            $a = [$a];
        }

        if (! is_array($b)) {
            $b = [$b];
        }

        $results = new ComparisonResult;

        $results->added = array_diff($b, $a);
        $results->same = array_intersect($a, $b);
        $results->removed = array_diff($a, $b);

        return $results;
    }
}
