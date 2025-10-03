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
 *     when1: new WhenThen(true, 'result1'),
 *     when2: new WhenThen(false, 'result2'),
 *     else: 'default result',
 * );
 * ```
 *
 * This will be generated into a SQL `CASE` expression like:
 *
 * ```sql
 * CASE
 *     WHEN TRUE THEN 'result1'
 *     WHEN FALSE THEN 'result2'
 *     ELSE 'default result'
 * END
 * ```
 *
 * Example with a specific case value:
 *
 * ```php
 * $case = new CaseX(
 *      'column_name',
 *      when1: new WhenThen('one', 'result1'),
 *      when2: new WhenThen('two', 'result2'),
 *      else: 'default result',
 * );
 * ```
 *
 * This will be generated into a SQL `CASE` expression like:
 *
 * ```sql
 * CASE "column_name"
 *     WHEN 'one' THEN 'result1'
 *     WHEN 'two' THEN 'result2'
 *     ELSE 'default result'
 * END
 * ```
 */
final class CaseX implements ExpressionInterface
{
    /**
     * @var WhenThen[] List of `WHEN-THEN` conditions and their corresponding results in the `CASE` expression.
     */
    public readonly array $whenThen;
    /**
     * @var mixed The result to return if no conditions match in the CASE expression.
     * If not set, the `CASE` expression will not have an `ELSE` clause.
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    public readonly mixed $else;

    /**
     * @param mixed $value Comparison condition in the `CASE` expression:
     * - `string` is treated as a table column name which will be quoted before usage in the SQL statement;
     * - `array` is treated as a condition to check, see {@see QueryInterface::where()};
     * - other values will be converted to their string representation using {@see QueryBuilderInterface::buildValue()}.
     * If not provided, the `CASE` expression will be a WHEN-THEN structure without a specific case value.
     * @param ColumnInterface|string $valueType Optional data type of the CASE expression which can be used in some DBMS
     * to specify the expected type (for example in PostgreSQL).
     * @param mixed|WhenThen ...$args List of `WHEN` conditions and their corresponding results represented
     * as {@see WhenThen} instances or `ELSE` value in the `CASE` expression.
     */
    public function __construct(
        public readonly mixed $value = null,
        public readonly string|ColumnInterface $valueType = '',
        mixed ...$args,
    ) {
        $whenThen = [];

        foreach ($args as $arg) {
            if ($arg instanceof WhenThen) {
                $whenThen[] = $arg;
            } elseif ($this->hasElse()) {
                throw new InvalidArgumentException('`CASE` expression can have only one `ELSE` value.');
            } else {
                $this->else = $arg;
            }
        }

        if (empty($whenThen)) {
            throw new InvalidArgumentException('`CASE` expression must have at least one `WHEN-THEN` clause.');
        }

        $this->whenThen = $whenThen;
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
