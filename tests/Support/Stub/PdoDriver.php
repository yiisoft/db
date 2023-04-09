<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Driver\Pdo\AbstractPdoDriver;

final class PdoDriver extends AbstractPdoDriver
{
    public function getDriverName(): string
    {
        return 'db';
    }
}
