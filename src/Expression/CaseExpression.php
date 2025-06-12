<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

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
 *     WHEN condition1 THEN result1
 *     WHEN condition2 THEN result2
 *     ELSE defaultResult
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
 *     WHEN 1 THEN result1
 *     WHEN 2 THEN result2
 *     ELSE defaultResult
 * END
 * ```
 */
final class CaseExpression implements ExpressionInterface
{
    /**
     * @var WhenClause[] List of pairs of conditions and their corresponding results in the CASE expression.
     */
    private array $whenClauses = [];
    /**
     * @var bool|ExpressionInterface|float|int|string|null The result to return if no conditions match in the CASE
     * expression. If not set, the CASE expression will not have an ELSE clause.
     *
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private bool|ExpressionInterface|float|int|string|null $else;

    /**
     * @param array|bool|ExpressionInterface|float|int|string|null $case Comparison condition in the CASE expression.
     * If not provided, the CASE expression will be a WHEN-THEN structure without a specific case value.
     */
    public function __construct(private array|bool|ExpressionInterface|float|int|string|null $case = null)
    {
    }

    /**
     * Adds a condition and its corresponding result to the CASE expression.
     *
     * @param array|bool|ExpressionInterface|float|int|string $when The condition to check (WHEN).
     * @param bool|ExpressionInterface|float|int|string|null $then The result to return if the condition is `true` (THEN).
     */
    public function addWhen(
        array|bool|ExpressionInterface|float|int|string $when,
        bool|ExpressionInterface|float|int|string|null $then,
    ): self {
        $this->whenClauses[] = new WhenClause($when, $then);
        return $this;
    }

    /**
     * Sets the value to compare against in the CASE expression.
     *
     * @param array|bool|ExpressionInterface|float|int|string|null $case Comparison condition in the CASE expression.
     * If not provided, the CASE expression will be a WHEN-THEN structure without a specific case value.
     */
    public function case(array|bool|ExpressionInterface|float|int|string|null $case): self
    {
        $this->case = $case;
        return $this;
    }

    /**
     * Sets the result to return if no conditions match in the CASE expression.
     *
     * @param bool|ExpressionInterface|float|int|string|null $else The result to return if no conditions match (ELSE).
     * If not set, the CASE expression will not have an ELSE clause.
     */
    public function else(bool|ExpressionInterface|float|int|string|null $else): self
    {
        $this->else = $else;
        return $this;
    }

    /**
     * Returns the comparison condition in the CASE expression.
     *
     * @psalm-mutation-free
     */
    public function getCase(): array|bool|ExpressionInterface|float|int|string|null
    {
        return $this->case;
    }

    /**
     * Returns the result to return if no conditions match in the CASE expression.
     *
     * @psalm-mutation-free
     */
    public function getElse(): bool|ExpressionInterface|float|int|string|null
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
     * @param WhenClause[] $whenClauses List of WHEN conditions and their corresponding results in the CASE expression.
     */
    public function setWhen(array $whenClauses): self
    {
        $this->whenClauses = $whenClauses;
        return $this;
    }
}
