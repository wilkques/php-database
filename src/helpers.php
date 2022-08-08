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

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}

if (!function_exists("str_contains")) {
    function str_contains(string $haystack, string $needle)
    {
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists("array_set")) {
    /**
     * Set an array item to a given value using "dot" notation.
     *
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array  $array
     * @param  string|null  $key
     * @param  mixed  $value
     * @return array
     */
    function array_set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }
}

if (!function_exists("array_get")) {
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function array_get($array, $key, $default = null)
    {
        if (!is_accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (array_exists_key($array, $key)) {
            return $array[$key];
        }

        if (!str_contains($key, '.')) {
            return $array[$key] ?? value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (is_accessible($array) && array_exists_key($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }
}

if (!function_exists("is_accessible")) {
    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     * @return bool
     */
    function is_accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }
}

if (!function_exists("array_exists_key")) {
    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  \ArrayAccess|array  $array
     * @param  string|int  $key
     * @return bool
     */
    function array_exists_key($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }
}

if (!function_exists("str_delimiter_replace")) {
    function str_delimiter_replace($value, $delimiter = '_', $case = MB_CASE_LOWER)
    {
        if (!ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = str_convert_case(preg_replace('/(.)(?=[A-Z])/u', '$1' . $delimiter, $value), $case);
        }

        return $value;
    }
}

if (!function_exists("str_convert_case")) {
    function str_convert_case($value, $case = MB_CASE_LOWER)
    {
        return mb_convert_case($value, $case, 'UTF-8');
    }
}