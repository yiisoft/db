<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;

interface LikeConditionInterface extends ConditionInterface, ExpressionInterface, SimpleConditionInterface
{
    /**
     * {see LikeConditionInterface::setEscapingReplacements}
     */
    public function getEscapingReplacements(): array|bool|null;

    /**
     * This method allows to specify how to escape special characters in the value(s).
     *
     * @param array|bool|null An array of mappings from the special characters to their escaped counterparts.
     * You may use `false` or an empty array to indicate the values are already escaped and no escape should be applied.
     * Note that when using an escape mapping (or the third operand is not provided), the values will be automatically
     * enclosed within a pair of percentage characters.
     */
    public function setEscapingReplacements(array|bool|null $escapingReplacements): void;
}
