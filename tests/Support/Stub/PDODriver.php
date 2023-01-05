<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Driver\PDO\AbstractPDODriver;
use Yiisoft\Db\Driver\PDO\PDODriverInterface;

final class PDODriver extends AbstractPDODriver implements PDODriverInterface
{
    public function getDriverName(): string
    {
        return 'db';
    }
}
