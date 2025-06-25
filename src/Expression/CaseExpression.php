<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Column\ColumnInterface;

use function array_key_exists;
use function get_object_vars;

/**
 * Represents a SQL CASE expression.
 *
 * A CASE expression allows conditional logic in SQL queries, returning different values based on specified conditions.
 * It can be used to implement complex logic directly in SQL statements.
 *
 * Example usage:
 *
 * ```php
 * $case = (new CaseExpression())
 *     ->addWhen('condition1', 'result1')
 *     ->addWhen('condition2', 'result2')
 *     ->else('defaultResult');
 * ```
 *
 * This will be generated into a SQL CASE expression like:
 *
 * ```sql
 * CASE
 *     WHEN condition1 THEN 'result1'
 *     WHEN condition2 THEN 'result2'
 *     ELSE 'defaultResult'
 * END
 * ```
 *
 * Example with a specific case value:
 *
 * ```php
 * $case = (new CaseExpression('expression'))
 *      ->addWhen(1, 'result1')
 *      ->addWhen(2, 'result2')
 *      ->else('defaultResult');
 * ```
 *
 * This will be generated into a SQL CASE expression like:
 *
 * ```sql
 * CASE expression
 *     WHEN 1 THEN 'result1'
 *     WHEN 2 THEN 'result2'
 *     ELSE 'defaultResult'
 * END
 * ```
 */
final class CaseExpression implements ExpressionInterface
{
    /**
     * @var WhenClause[] List of WHEN conditions and their corresponding results in the CASE expression.
     */
    private array $whenClauses;
    /**
     * @var mixed The result to return if no conditions match in the CASE expression.
     * If not set, the CASE expression will not have an ELSE clause.
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private mixed $else;

    /**
     * @param mixed $case Comparison condition in the CASE expression:
     * - `string` is treated as a SQL expression;
     * - `array` is treated as a condition to check, see {@see QueryInterface::where()};
     * - other values will be converted to their string representation using {@see QueryBuilderInterface::buildValue()}.
     * If not provided, the CASE expression will be a WHEN-THEN structure without a specific case value.
     * @param ColumnInterface|string $caseType Optional data type of the CASE expression which can be used in some DBMS
     * to specify the expected type (for example in PostgreSQL).
     * @param WhenClause ...$when List of WHEN conditions and their corresponding results in the CASE expression.
     */
    public function __construct(
        private mixed $case = null,
        private string|ColumnInterface $caseType = '',
        WhenClause ...$when,
    ) {
        $this->whenClauses = $when;
    }

    /**
     * Adds a condition and its corresponding result to the CASE expression.
     *
     * @param mixed $when The condition to check (WHEN):
     * - `string` is treated as a SQL expression;
     * - `array` is treated as a condition to check, see {@see QueryInterface::where()};
     * - other values will be converted to their string representation using {@see QueryBuilderInterface::buildValue()}.
     * @param mixed $then The result to return if the condition is `true` (THEN):
     * - `string` is treated as a SQL expression;
     * - other values will be converted to their string representation using {@see QueryBuilderInterface::buildValue()}.
     */
    public function addWhen(mixed $when, mixed $then): self
    {
        $this->whenClauses[] = new WhenClause($when, $then);
        return $this;
    }

    /**
     * Sets the value to compare against in the CASE expression.
     *
     * @param mixed $case Comparison condition in the CASE expression:
     * - `string` is treated as a SQL expression;
     * - `array` is treated as a condition to check, see {@see QueryInterface::where()};
     * - other values will be converted to their string representation using {@see QueryBuilderInterface::buildValue()}.
     * If not provided, the CASE expression will be a WHEN-THEN structure without a specific case value.
     */
    public function case(mixed $case): self
    {
        $this->case = $case;
        return $this;
    }

    /**
     * Sets the optional data type of the CASE expression which can be used in some DBMS to specify the expected type
     * (for example in PostgreSQL).
     */
    public function caseType(string|ColumnInterface $caseType): self
    {
        $this->caseType = $caseType;
        return $this;
    }

    /**
     * Sets the result to return if no conditions match in the CASE expression.
     *
     * @param mixed $else The result to return if no conditions match (ELSE).
     * - `string` is treated as a SQL expression;
     * - other values will be converted to their string representation using {@see QueryBuilderInterface::buildValue()}.
     * If not set, the CASE expression will not have an ELSE clause.
     */
    public function else(mixed $else): self
    {
        $this->else = $else;
        return $this;
    }

    /**
     * Returns the comparison condition in the CASE expression.
     *
     * @psalm-mutation-free
     */
    public function getCase(): mixed
    {
        return $this->case;
    }

    /**
     * Returns the data type of the CASE expression.
     *
     * @psalm-mutation-free
     */
    public function getCaseType(): string|ColumnInterface
    {
        return $this->caseType;
    }

    /**
     * Returns the result to return if no conditions match in the CASE expression.
     *
     * @psalm-mutation-free
     */
    public function getElse(): mixed
    {
        return $this->else ?? null;
    }

    /**
     * Returns WHEN conditions and their corresponding results in the CASE expression.
     *
     * @return WhenClause[] List of WHEN conditions and their corresponding results in the CASE expression.
     *
     * @psalm-mutation-free
     */
    public function getWhen(): array
    {
        return $this->whenClauses;
    }

    /**
     * Returns `true` if the CASE expression has an ELSE clause, `false` otherwise.
     *
     * @psalm-mutation-free
     */
    public function hasElse(): bool
    {
        return array_key_exists('else', get_object_vars($this));
    }

    /**
     * Sets WHEN conditions and their corresponding results in the CASE expression.
     *
     * @param WhenClause ...$whenClauses List of WHEN conditions and their corresponding results in the CASE expression.
     */
    public function setWhen(WhenClause ...$whenClauses): self
    {
        $this->whenClauses = $whenClauses;
        return $this;
    }
}
