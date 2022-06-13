<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Interface;

use Iterator;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Expression\ExpressionInterface;

interface LikeConditionInterface extends ConditionInterface
{
    /**
     * @psalm-return string|Expression The column name.
     */
    public function getColumn(): string|Expression;

    /**
     * {see setEscapingReplacements}
     */
    public function getEscapingReplacements(): ?array;

    /**
     * This method allows specifying how to escape special characters in the value(s).
     *
     * @param array|null An array of mappings from the special characters to their escaped counterparts.
     * You may use an empty array to indicate the values are already escaped and no escape should be applied.
     * Note that when using an escape mapping (or the third operand is not provided), the values will be automatically
     * enclosed within a pair of percentage characters.
     */
    public function setEscapingReplacements(array|null $escapingReplacements): void;

    /**
     * @return string The operator to use. Anything could be used e.g. `>`, `<=`, etc.
     */
    public function getOperator(): string;

    /**
     * @return array|ExpressionInterface|int|Iterator|string|null The value to the right of the {@see operator}.
     */
    public function getValue(): array|int|string|Iterator|ExpressionInterface|null;
}
