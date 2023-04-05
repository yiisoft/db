<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

final class Driver extends \Yiisoft\Db\Driver\Pdo\AbstractDriver
{
    public function getDriverName(): string
    {
        return 'db';
    }
}
