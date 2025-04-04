<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

/**
 * Represents an SQL statement used for batch commands execution.
 */
class QueryStatement
{
    /**
     * @param string $sql SQL query.
     * @param array $params Parameters for query execution.
     */
    public function __construct(public string $sql, public array $params = [])
    {
    }
}
