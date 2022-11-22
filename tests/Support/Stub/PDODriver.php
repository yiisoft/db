<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use PDO;
use Yiisoft\Db\Driver\PDO\PDODriverInterface;

final class PDODriver extends \Yiisoft\Db\Driver\PDO\PDODriver implements PDODriverInterface
{
    public function getDriverName(): string
    {
        return 'db';
    }
}
