<?php

namespace Yiisoft\Db\Query;

use Yiisoft\Db\Command\ParamInterface;

abstract class AbstractQueryStatement implements QueryStatement
{
    /**
     * @var string The SQL statement to execute.
     */
    private string $sql = '';

    /**
     * @var array|ParamInterface[] Parameters to use.
     */
    private array $params = [];

    public function getSql(): string
    {
        return $this->sql;
    }

    public function setSql(string $sql): static
    {
        $this->sql = $sql;
        return $this;
    }

    public function getParams(bool $asValues = true): array
    {
        return $this->params;
    }

    public function setParams(array $params): static
    {
        $this->params = $params;
        return $this;
    }
}
