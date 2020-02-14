<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Tests\ConnectionTest as AbstractConnectionTest;

final class ConnectionTest extends AbstractConnectionTest
{
    protected ?string $driverName = 'mysql';
}
