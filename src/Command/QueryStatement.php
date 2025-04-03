<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

/**
 * Represents an SQL statement used for batch commands execution.
 */
class QueryStatement
{
    public function __construct(public string $sql, public array $params = [])
    {
    }
}
