<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stubs;

use PDO;
use Yiisoft\Db\Driver\PDO\PDODriverInterface;

final class PDODriver extends \Yiisoft\Db\Driver\PDO\PDODriver implements PDODriverInterface
{
    public function __construct(string $dsn)
    {
        parent::__construct($dsn);
    }

    public function createConnection(): PDO
    {
        return parent::createConnection();
    }

    public function getDriverName(): string
    {
        return 'sqlite';
    }
}
