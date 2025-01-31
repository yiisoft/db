<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

use Closure;

use Yiisoft\Db\Query\QueryInterface;

use function array_column;
use function array_combine;
use function array_map;
use function array_multisort;
use function count;
use function is_string;
use function range;

/**
 * Array manipulation methods.
 *
 * @psalm-import-type IndexBy from QueryInterface
 */
final class DbArrayHelper
{
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
     * @param array[] $array The array that needs to be indexed or arranged.
     * @param Closure|string|null $indexBy The column name or anonymous function which result will be used to index the
     * array. If the array does not have the key, the ordinal indexes will be used if `$arrangeBy` is not specified or
     * a warning will be triggered if `$arrangeBy` is specified.
     * @param string[] $arrangeBy The array of keys that will be used to arrange the input array by one or more keys.
     *
     * @return array[] The indexed and/or arranged array.
     *
     * @psalm-param IndexBy|null $indexBy
     * @psalm-suppress MixedArrayAssignment
     */
    public static function index(array $array, Closure|string|null $indexBy = null, array $arrangeBy = []): array
    {
        if (empty($array) || $indexBy === null && empty($arrangeBy)) {
            return $array;
        }

        if (empty($arrangeBy)) {
            if (is_string($indexBy)) {
                return array_column($array, null, $indexBy);
            }

            return array_combine(array_map($indexBy, $array), $array);
        }

        $result = [];

        foreach ($array as $element) {
            $lastArray = &$result;

            foreach ($arrangeBy as $group) {
                $value = (string) $element[$group];

                if (!isset($lastArray[$value])) {
                    $lastArray[$value] = [];
                }

                $lastArray = &$lastArray[$value];
            }

            if ($indexBy === null) {
                $lastArray[] = $element;
            } else {
                if (is_string($indexBy)) {
                    $value = $element[$indexBy];
                } else {
                    $value = $indexBy($element);
                }

                $lastArray[(string) $value] = $element;
            }

            unset($lastArray);
        }

        /** @var array[] $result */
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
     *
     * @psalm-template T
     * @psalm-param array<T> $array
     * @psalm-param-out array<T> $array
     */
    public static function multisort(
        array &$array,
        string $key
    ): void {
        if (empty($array)) {
            return;
        }

        $column = array_column($array, $key);

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
}
