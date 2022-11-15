<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

final class BaseConnectionProvider
{
    public function execute(): array
    {
        return [
            ['SQLSTATE[HY000]: General error: 1 near "bad": syntax error'],
        ];
    }
}
