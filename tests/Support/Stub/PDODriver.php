<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Driver\PDO\AbstractPDODriver;

final class PDODriver extends AbstractPDODriver
{
    public function getDriverName(): string
    {
        return 'db';
    }
}
