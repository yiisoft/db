<?php

namespace Yiisoft\Db\Query;

use Yiisoft\Db\Command\ParamInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

abstract class AbstractQueryStatement
{
    /**
     * @var string The SQL statement to execute.
     */
    private string $sql = '';

    /**
     * @var ParamInterface[] Parameters to use.
     */
    private array $params = [];

    /**
     * @return QueryBuilderInterface The query builder instance.
     */
    abstract protected function getQueryBuilder(): QueryBuilderInterface;

    public function getSql(): string
    {
        return $this->sql;
    }

    public function setSql(string $sql): static
    {
        $this->sql = $this->getQueryBuilder()->quoter()->quoteSql($sql);
        return $this;
    }
}
