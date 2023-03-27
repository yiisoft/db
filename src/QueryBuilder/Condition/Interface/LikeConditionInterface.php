<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

use Iterator;
use Yiisoft\Db\Expression\ExpressionInterface;

/**
 * Represents a condition that's based on `LIKE` operator.
 */
interface LikeConditionInterface extends ConditionInterface
{
    /**
     * @return ExpressionInterface|string The column name.
     */
    public function getColumn(): string|ExpressionInterface;

    /**
     * @see setEscapingReplacements()
     */
    public function getEscapingReplacements(): array|null;

    /**
     * This method allows specifying how to escape special characters in the value(s).
     *
     * @param array|null $escapingReplacements An array of mappings from the special characters to their escaped
     * counterparts.
     *
     * You may use an empty array to indicate the values are already escaped and no escape should be applied.
     * Note that when using an escape mapping (or the third operand isn't provided), the values will be automatically
     * inside within a pair of percentage characters.
     */
    public function setEscapingReplacements(array|null $escapingReplacements): void;

    /**
     * @return string The operator to use such as `>` or `<=`.
     */
    public function getOperator(): string;

    /**
     * @return array|ExpressionInterface|int|Iterator|string|null The value to the right of {@see operator}.
     */
    public function getValue(): array|int|string|Iterator|ExpressionInterface|null;
}
