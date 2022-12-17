<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Interface;

/**
 * @codeCoverageIgnore
 */
interface HashConditionInterface extends ConditionInterface
{
    /**
     * @return array|null The condition specification.
     */
    public function getHash(): ?array;
}
