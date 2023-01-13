<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

use ArrayAccess;
use Closure;

/**
 * Short implementation of ArrayHelper from Yii2
 */
class ArrayHelper {
    /**
     * Checks if the given array contains the specified key.
     * This method enhances the `array_key_exists()` function by supporting case-insensitive
     * key comparison.
     * @param string $key the key to check
     * @param ArrayAccess|array $array the array with keys to check
     * @return bool whether the array contains the specified key
     */
    public static function keyExists(string $key, ArrayAccess|array $array): bool
    {
        // Function `isset` checks key faster but skips `null`, `array_key_exists` handles this case
        // https://www.php.net/manual/en/function.array-key-exists.php#107786
        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            return true;
        }
        // Cannot use `array_has_key` on Objects for PHP 7.4+, therefore we need to check using [[ArrayAccess::offsetExists()]]
        return $array instanceof ArrayAccess && $array->offsetExists($key);
    }

    /**
     * Retrieves the value of an array element or object property with the given key or property name.
     * If the key does not exist in the array, the default value will be returned instead.
     * Not used when getting value from an object.
     *
     * The key may be specified in a dot format to retrieve the value of a sub-array or the property
     * of an embedded object. In particular, if the key is `x.y.z`, then the returned value would
     * be `$array['x']['y']['z']` or `$array->x->y->z` (if `$array` is an object). If `$array['x']`
     * or `$array->x` is neither an array nor an object, the default value will be returned.
     * Note that if the array already has an element `x.y.z`, then its value will be returned
     * instead of going through the sub-arrays. So it is better to be done specifying an array of key names
     * like `['x', 'y', 'z']`.
     *
     * Below are some usage examples,
     *
     * ```php
     * // working with array
     * $username = ArrayHelper::getValueByPath($_POST, 'username');
     * // working with object
     * $username = ArrayHelper::getValueByPath($user, 'username');
     * // working with anonymous function
     * $fullName = ArrayHelper::getValueByPath($user, function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     * });
     * // using dot format to retrieve the property of embedded object
     * $street = \yii\helpers\ArrayHelper::getValue($users, 'address.street');
     * // using an array of keys to retrieve the value
     * $value = \yii\helpers\ArrayHelper::getValue($versions, ['1.0', 'date']);
     * ```
     *
     * @param object|array $array array or object to extract value from
     * @param Closure|string $key key name of the array element, an array of keys or property name of the object,
     * or an anonymous function returning the value. The anonymous function signature should be:
     * `function($array, $defaultValue)`.
     * The possibility to pass an array of keys is available since version 2.0.4.
     * @param mixed|null $default the default value to be returned if the specified array key does not exist. Not used when
     * getting value from an object.
     * @return mixed the value of the element if found, default value otherwise
     */
    public static function getValueByPath(object|array $array, Closure|string $key, mixed $default = null)
    {
        if ($key instanceof Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = static::getValueByPath($array, $keyPart);
            }
            $key = $lastKey ?? '';
        }

        if (is_object($array) && property_exists($array, $key)) {
            return $array->$key;
        }
        if (static::keyExists($key, $array)) {
            return $array[$key];
        }

        if ($key && ($pos = strrpos($key, '.')) !== false) {
            $array = static::getValueByPath($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (is_object($array)) {
            // this is expected to fail if the property does not exist, or __get() is not implemented
            // it is not reliably possible to check whether a property is accessible beforehand
            try {
                return $array->$key;
            } catch (\Exception $e) {
                if ($array instanceof ArrayAccess) {
                    return $default;
                }
                throw $e;
            }
        }

        if (static::keyExists($key, $array)) {
            return $array[$key];
        }

        return $default;
    }
}
