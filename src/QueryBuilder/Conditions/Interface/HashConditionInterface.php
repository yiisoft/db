<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Conditions\Interface;

interface HashConditionInterface extends ConditionInterface
{
    /**
     * @return array|null The condition specification.
     */
    public function getHash(): ?array;
}
