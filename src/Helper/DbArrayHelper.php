<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

use Closure;
use Exception;

use function array_key_exists;
use function array_map;
use function array_multisort;
use function count;
use function is_array;
use function is_object;
use function property_exists;
use function range;
use function strrpos;
use function substr;

/**
 * Array manipulation methods.
 */
final class DbArrayHelper
{
    /**
     * Returns the values of a specified column in an array.
     *
     * The input array should be multidimensional or an array of objects.
     *
     * For example,
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc'],
     *     ['id' => '345', 'data' => 'def'],
     * ];
     * $result = DbArrayHelper::getColumn($array, 'id');
     * // the result is: ['123', '345']
     *
     * // using anonymous function
     * $result = DbArrayHelper::getColumn($array, function ($element) {
     *     return $element['id'];
     * });
     * ```
     *
     * @param array $array Array to extract values from.
     * @param string $name The column name.
     *
     * @return array The list of column values.
     */
    public static function getColumn(array $array, string $name): array
    {
        return array_map(
            static function (array|object $element) use ($name): mixed {
                return self::getValueByPath($element, $name);
            },
            $array
        );
    }

    /**
     * Retrieves the value of an array element or object property with the given key or property name.
     *
     * If the key doesn't exist in the array, the default value will be returned instead.
     *
     * Not used when getting value from an object.
     *
     * The key may be specified in a dot format to retrieve the value of a sub-array or the property of an embedded
     * object.
     *
     * In particular, if the key is `x.y.z`, then the returned value would be `$array['x']['y']['z']` or
     * `$array->x->y->z` (if `$array` is an object).
     *
     * If `$array['x']` or `$array->x` is neither an array nor an object, the default value will be returned.
     *
     * Note that if the array already has an element `x.y.z`, then its value will be returned instead of going through
     * the sub-arrays.
     *
     * So it's better to be done specifying an array of key names like `['x', 'y', 'z']`.
     *
     * Below are some usage examples.
     *
     * ```php
     * // working with array
     * $username = DbArrayHelper::getValueByPath($_POST, 'username');
     * // working with object
     * $username = DbArrayHelper::getValueByPath($user, 'username');
     * // working with anonymous function
     * $fullName = DbArrayHelper::getValueByPath($user, function ($user, $defaultValue) {
     *     return $user->firstName . ' ' . $user->lastName;
     * });
     * // using dot format to retrieve the property of embedded object
     * $street = \yii\helpers\DbArrayHelper::getValue($users, 'address.street');
     * // using an array of keys to retrieve the value
     * $value = \yii\helpers\DbArrayHelper::getValue($versions, ['1.0', 'date']);
     * ```
     *
     * @param array|object $array Array or object to extract value from.
     * @param Closure|string $key Key name of the array element, an array of keys or property name of the object, or an
     * anonymous function returning the value. The anonymous function signature should be:
     * `function($array, $defaultValue)`.
     * @param mixed|null $default The default value to be returned if the specified array key doesn't exist. Not used
     * when getting value from an object.
     *
     * @return mixed The value of the element if found, default value otherwise
     */
    public static function getValueByPath(object|array $array, Closure|string $key, mixed $default = null): mixed
    {
        if ($key instanceof Closure) {
            return $key($array, $default);
        }

        if (is_object($array) && property_exists($array, $key)) {
            return $array->$key;
        }

        if (is_array($array) && array_key_exists($key, $array)) {
            return $array[$key];
        }

        if ($key && ($pos = strrpos($key, '.')) !== false) {
            /** @psalm-var array<string, mixed>|object $array */
            $array = self::getValueByPath($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (is_object($array)) {
            /**
             * This is expected to fail if the property doesn't exist, or __get() isn't implemented it isn't reliably
             * possible to check whether a property is accessible beforehand
             */
            return $array->$key;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        return $default;
    }

    /**
     * Indexes and/or groups the array according to a specified key.
     *
     * The input should be either a multidimensional array or an array of objects.
     *
     * The $key can be either a key name of the sub-array, a property name of an object, or an anonymous function that
     * must return the value that will be used as a key.
     *
     * $groups is an array of keys, that will be used to group the input array into one or more sub-arrays based on keys
     * specified.
     *
     * If the `$key` is specified as `null` or a value of an element corresponding to the key is `null` in addition
     * to `$groups` not specified then the element is discarded.
     *
     * For example:
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *     ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     * ];
     * $result = DbArrayHelper::index($array, 'id');
     * ```
     *
     * The result will be an associative array, where the key is the value of `id` attribute
     *
     * ```php
     * [
     *     '123' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     '345' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *     // The second element of an original array is overwritten by the last element because of the same id
     * ]
     * ```
     *
     * Passing `id` as a third argument will group `$array` by `id`:
     *
     * ```php
     * $result = DbArrayHelper::index($array, null, 'id');
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by `device` on the second level
     * and indexed by `data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *     ],
     *     '345' => [ // all elements with this index are present in the result array
     *         ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *         ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     *     ]
     * ]
     * ```
     *
     * The result will be a multidimensional array grouped by `id` on the first level, by the `device` on the second one
     * and indexed by the `data` on the third level:
     *
     * ```php
     * [
     *     '123' => [
     *         'laptop' => [
     *             'abc' => ['id' => '123', 'data' => 'abc', 'device' => 'laptop']
     *         ]
     *     ],
     *     '345' => [
     *         'tablet' => [
     *             'def' => ['id' => '345', 'data' => 'def', 'device' => 'tablet']
     *         ],
     *         'smartphone' => [
     *             'hgi' => ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone']
     *         ]
     *     ]
     * ]
     * ```
     *
     * @param array $array The array that needs to be indexed or grouped.
     * @param string|null $key The column name or anonymous function which result will be used to index the array.
     * @param array $groups The array of keys that will be used to group the input array by one or more keys. If the
     * $key attribute or its value for the particular element is null and $groups aren't defined.
     * The array element will be discarded.
     * Otherwise, if $groups are specified, an array element will be added to the result array without any key.
     *
     * @throws Exception
     *
     * @return array The indexed and/or grouped array.
     *
     * @psalm-param array[] $array The array that needs to be indexed or grouped.
     * @psalm-param string[] $groups The array of keys that will be used to group the input array by one or more keys.
     *
     * @psalm-suppress MixedArrayAssignment
     */
    public static function index(array $array, string|null $key = null, array $groups = []): array
    {
        $result = [];

        foreach ($array as $element) {
            $lastArray = &$result;

            foreach ($groups as $group) {
                /** @psalm-var string $value */
                $value = self::getValueByPath($element, $group);
                if (!array_key_exists($value, $lastArray)) {
                    $lastArray[$value] = [];
                }
                $lastArray = &$lastArray[$value];
            }

            if ($key === null) {
                if (!empty($groups)) {
                    $lastArray[] = $element;
                }
            } else {
                /** @psalm-var mixed $value */
                $value = self::getValueByPath($element, $key);

                if ($value !== null) {
                    $lastArray[(string) $value] = $element;
                }
            }

            unset($lastArray);
        }

        /** @psalm-var array $result */
        return $result;
    }

    /**
     * Returns a value indicating whether the given array is an associative array.
     *
     * An array is associative if all its keys are strings.
     *
     * Note that an empty array won't be considered associative.
     *
     * @param array $array The array being checked.
     *
     * @return bool Whether the array is associative.
     */
    public static function isAssociative(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        foreach ($array as $key => $_value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sorts an array of objects or arrays (with the same structure) by one or several keys.
     *
     * @param array $array The array to be sorted. The array will be modified after calling this method.
     * @param string $key The key(s) to be sorted by.
     */
    public static function multisort(
        array &$array,
        string $key
    ): void {
        if (empty($array)) {
            return;
        }

        $column = self::getColumn($array, $key);

        array_multisort(
            $column,
            SORT_ASC,
            SORT_NUMERIC,

            /**
             * This fix is used for cases when the main sorting specified by columns has equal values without it will
             * lead to Fatal Error: Nesting level too deep - recursive dependency?
             */
            range(1, count($array)),
            SORT_ASC,
            SORT_NUMERIC,
            $array
        );
    }

    /**
     * Returns the value of an array element or object property with the given path.
     *
     * This method is internally used to convert the data fetched from a database into the format as required by this
     * query.
     *
     * @param array[] $rows The raw query result from a database.
     *
     * @return array[]
     */
    public static function populate(array $rows, Closure|string|null $indexBy = null): array
    {
        if ($indexBy === null) {
            return $rows;
        }

        $result = [];

        foreach ($rows as $row) {
            /** @psalm-suppress MixedArrayOffset */
            $result[self::getValueByPath($row, $indexBy)] = $row;
        }

        return $result;
    }
}
