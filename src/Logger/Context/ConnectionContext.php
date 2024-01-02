<?php

declare(strict_types=1);

namespace Yiisoft\Db\Logger\Context;

final class ConnectionContext extends AbstractContext
{
    public function __construct(string $methodName, private string $dsn)
    {
        parent::__construct($methodName);
    }

    public function getDsn(): string
    {
        return $this->dsn;
    }
}
