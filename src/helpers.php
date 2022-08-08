<?php

if (!function_exists("array_only")) {
    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     * 
     * @return array
     */
    function array_only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}

if (!function_exists("array_field")) {
    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array  $keys
     * 
     * @return array
     */
    function array_field($array, $keys)
    {
        uksort($array, function ($a, $b) use ($keys) {
            return array_search($a, $keys) <=> array_search($b, $keys);
        });

        return $array;
    }
}
