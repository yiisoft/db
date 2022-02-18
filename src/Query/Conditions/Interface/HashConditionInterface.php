<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Conditions\Interface;

use Yiisoft\Db\Expression\ExpressionInterface;

interface HashConditionInterface extends ConditionInterface, ExpressionInterface
{
    /**
     * @return array|null The condition specification.
     */
    public function getHash(): ?array;
}
