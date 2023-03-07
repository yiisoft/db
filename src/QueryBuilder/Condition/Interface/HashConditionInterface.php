<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

/**
 * Should be implemented by classes that represent a hash condition.
 */
interface HashConditionInterface extends ConditionInterface
{
    /**
     * @return array|null The condition specification.
     */
    public function getHash(): ?array;
}
