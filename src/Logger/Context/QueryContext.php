<?php

declare(strict_types=1);

namespace Yiisoft\Db\Logger\Context;

final class QueryContext extends AbstractContext
{
    public function __construct(string $methodName, private string $rawSql, private string $category)
    {
        parent::__construct($methodName);
    }

    public function getRawSql(): string
    {
        return $this->rawSql;
    }

    public function getCategory(): string
    {
        return $this->category;
    }
}
