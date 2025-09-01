<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Statement;

use InvalidArgumentException;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_key_exists;
use function get_object_vars;

/**
 * Represents a SQL `CASE` expression.
 *
 * A `CASE` expression allows conditional logic in SQL queries, returning different values based on specified conditions.
 * It can be used to implement complex logic directly in SQL statements.
 *
 * Example usage:
 *
 * ```php
 * $case = new CaseX(
 *     when1: new When('condition1', 'result1'),
 *     when2: new When('condition2', 'result2'),
 *     else: 'defaultResult',
 * );
 * ```
 *
 * This will be generated into a SQL `CASE` expression like:
 *
 * ```sql
 * CASE
 *     WHEN condition1 THEN result1
 *     WHEN condition2 THEN result2
 *     ELSE defaultResult
 * END
 * ```
 *
 * Example with a specific case value:
 *
 * ```php
 * $case = new CaseX(
 *      'expression',
 *      when1: new When(1, 'result1'),
 *      when2: new When(2, 'result2'),
 *      else: 'defaultResult',
 * );
 * ```
 *
 * This will be generated into a SQL `CASE` expression like:
 *
 * ```sql
 * CASE expression
 *     WHEN 1 THEN result1
 *     WHEN 2 THEN result2
 *     ELSE defaultResult
 * END
 * ```
 */
final class CaseX implements ExpressionInterface
{
    /**
     * @var When[] List of `WHEN` conditions and their corresponding results in the `CASE` expression.
     */
    public readonly array $when;
    /**
     * @var mixed The result to return if no conditions match in the CASE expression.
     * If not set, the `CASE` expression will not have an `ELSE` clause.
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    public readonly mixed $else;

    /**
     * @param mixed $value Comparison condition in the `CASE` expression:
     * - `string` is treated as a SQL expression;
     * - `array` is treated as a condition to check, see {@see QueryInterface::where()};
     * - other values will be converted to their string representation using {@see QueryBuilderInterface::buildValue()}.
     * If not provided, the `CASE` expression will be a WHEN-THEN structure without a specific case value.
     * @param ColumnInterface|string $valueType Optional data type of the CASE expression which can be used in some DBMS
     * to specify the expected type (for example in PostgreSQL).
     * @param mixed|When ...$args List of `WHEN` conditions and their corresponding results represented
     * as {@see When} instances or `ELSE` value in the `CASE` expression.
     */
    public function __construct(
        public readonly mixed $value = null,
        public readonly string|ColumnInterface $valueType = '',
        mixed ...$args,
    ) {
        $when = [];

        foreach ($args as $arg) {
            if ($arg instanceof When) {
                $when[] = $arg;
            } elseif ($this->hasElse()) {
                throw new InvalidArgumentException('`CASE` expression can have only one `ELSE` value.');
            } else {
                $this->else = $arg;
            }
        }

        if (empty($when)) {
            throw new InvalidArgumentException('`CASE` expression must have at least one `WHEN` clause.');
        }

        $this->when = $when;
    }

    /**
     * Returns `true` if the `CASE` expression has an `ELSE` clause, `false` otherwise.
     *
     * @psalm-mutation-free
     */
    public function hasElse(): bool
    {
        return array_key_exists('else', get_object_vars($this));
    }
}
