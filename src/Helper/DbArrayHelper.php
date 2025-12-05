<?php

declare(strict_types=1);

namespace Yiisoft\Db\Helper;

use Closure;
use JsonSerializable;
use Traversable;
use Yiisoft\Db\Constant\GettypeResult;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;

use function array_column;
use function array_combine;
use function array_map;
use function array_multisort;
use function count;
use function get_object_vars;
use function gettype;
use function is_array;
use function is_string;
use function iterator_to_array;
use function range;

/**
 * Array manipulation methods.
 *
 * @psalm-import-type IndexBy from QueryInterface
 * @psalm-import-type ResultCallback from QueryInterface
 */
final class DbArrayHelper
{
    /**
     * Arranges the array of rows according to specified keys.
     *
     * For example:
     *
     * ```php
     * $array = [
     *     ['id' => '123', 'data' => 'abc', 'device' => 'laptop'],
     *     ['id' => '345', 'data' => 'def', 'device' => 'tablet'],
     *     ['id' => '345', 'data' => 'hgi', 'device' => 'smartphone'],
     * ];
     * $result = DbArrayHelper::arrange($rows, ['id']);
     * ```
     *
     * The result will be a multidimensional array arranged by `id`:
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
     * Another example:
     *
     * ```php
     *  $result = DbArrayHelper::arrange($rows, ['id', 'device'], 'data');
     *  ```
     *
     * The result will be a multidimensional array arranged by `id` on the first level, by `device` on the second level
     * and indexed by `data` on the third level:
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
     * @param array[] $rows The array of rows that needs to be arranged.
     * @param string[] $arrangeBy The array of keys that will be used to arrange the input array by one or more keys.
     * @param Closure|string|null $indexBy The column name or anonymous function which result will be used to index the
     * array.
     * @param Closure|null $resultCallback The callback function that will be called with the result array. This can be
     * used to modify the result before returning it.
     *
     * @return array[]|object[] The arranged array.
     *
     * @psalm-param list<array<string,mixed>> $rows
     * @psalm-param IndexBy|null $indexBy
     * @psalm-param ResultCallback|null $resultCallback
     */
    public static function arrange(
        array $rows,
        array $arrangeBy = [],
        Closure|string|null $indexBy = null,
        ?Closure $resultCallback = null,
    ): array {
        if (empty($rows)) {
            return [];
        }

        if (empty($arrangeBy)) {
            return self::index($rows, $indexBy, $resultCallback);
        }

        $arranged = [];

        foreach ($rows as $element) {
            $lastArray = &$arranged;

            foreach ($arrangeBy as $group) {
                $value = (string) $element[$group];

                if (!isset($lastArray[$value])) {
                    $lastArray[$value] = [];
                }

                $lastArray = &$lastArray[$value];
            }

            /** @psalm-suppress MixedArrayAssignment */
            $lastArray[] = $element;

            unset($lastArray);
        }

        /** @var array[] $arranged */
        if ($indexBy !== null || $resultCallback !== null) {
            self::indexArranged($arranged, $indexBy, $resultCallback, count($arrangeBy));
        }

        return $arranged;
    }

    /**
     * Indexes the array of rows according to a specified key.
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
     * @param array[] $rows The array of rows that needs to be indexed.
     * @param Closure|string|null $indexBy The column name or anonymous function which result will be used to index the
     * array.
     * @param Closure|null $resultCallback The callback function that will be called with the result array. This can be
     * used to modify the result before returning it.
     *
     * @return array[]|object[] The indexed array.
     *
     * @psalm-param array<array<string,mixed>> $rows
     * @psalm-param IndexBy|null $indexBy
     * @psalm-param ResultCallback|null $resultCallback
     * @psalm-return array<array<string,mixed>|object>
     */
    public static function index(
        array $rows,
        Closure|string|null $indexBy = null,
        ?Closure $resultCallback = null,
    ): array {
        if (empty($rows)) {
            return [];
        }

        if ($indexBy !== null) {
            if (is_string($indexBy)) {
                $indexes = array_column($rows, $indexBy);
            } else {
                $indexes = array_map($indexBy, $rows);
            }
        }

        if ($resultCallback !== null) {
            $rows = ($resultCallback)($rows);
        }

        /** @psalm-suppress MixedArgument */
        return !empty($indexes) ? array_combine($indexes, $rows) : $rows;
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
        string $key,
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
            $array,
        );
    }

    /**
     * Converts an object into an array.
     *
     * @param array|object $object The object to be converted into an array.
     *
     * @return array The array representation of the object.
     */
    public static function toArray(array|object $object): array
    {
        if (is_array($object)) {
            return $object;
        }

        if ($object instanceof JsonSerializable) {
            /** @var array */
            return $object->jsonSerialize();
        }

        if ($object instanceof Traversable) {
            return iterator_to_array($object);
        }

        return get_object_vars($object);
    }

    /**
     * Normalizes raw input into an array of expression values.
     *
     * @param array|ExpressionInterface|string $raw Raw input to be normalized. It can be:
     *  - an array of expression values;
     *  - a single expression object;
     *  - a string with comma-separated expression values.
     *
     * @return array An array of normalized expressions.
     *
     * @psalm-template TArray as array
     * @psalm-template TExpression as ExpressionInterface
     * @psalm-param TArray|TExpression|string $raw
     * @psalm-return ($raw is string ? list<string> : ($raw is ExpressionInterface ? list{TExpression} : TArray))
     *
     * @psalm-suppress InvalidFalsableReturnType Psalm cannot correct parse method code.
     */
    public static function normalizeExpressions(array|ExpressionInterface|string $raw): array
    {
        /**
         * @psalm-suppress PossiblyInvalidArgument,FalsableReturnStatement
         */
        return match (gettype($raw)) {
            GettypeResult::ARRAY => $raw,
            GettypeResult::STRING => preg_split('/\s*,\s*/', trim($raw), -1, PREG_SPLIT_NO_EMPTY),
            default => [$raw],
        };
    }

    /**
     * Recursively indexes the arranged array.
     *
     * @psalm-assert array[]|object[] $arranged
     * @psalm-param IndexBy|null $indexBy
     * @psalm-param ResultCallback|null $resultCallback
     */
    private static function indexArranged(
        array &$arranged,
        Closure|string|null $indexBy,
        ?Closure $resultCallback,
        int $depth,
    ): void {
        /** @psalm-var list<array<string,mixed>> $rows */
        foreach ($arranged as &$rows) {
            if ($depth > 1) {
                self::indexArranged($rows, $indexBy, $resultCallback, $depth - 1);
            } else {
                $rows = self::index($rows, $indexBy, $resultCallback);
            }
        }
    }
}
