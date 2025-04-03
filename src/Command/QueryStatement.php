<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

/**
 * Represents an SQL statement used for batch commands execution.
 */
class QueryStatement
{
    public string $sql;

    public array $params = [];

    public function __construct($sql, array $params = [])
    {
        $this->sql = $sql;
        $this->params = $params;
    }
}
