<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

/**
 * Represents an SQL statement parameters used for batch commands execution.
 */
class QueryStatementParameters
{
    public array $values = [];

    public array $params = [];
}
