<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Contracts\ConnectionInterface;

/**
 * @group db
 * @group mysql
 */
class ActiveQueryTest extends \Yiisoft\Db\Tests\ActiveQueryTest
{
    protected ?ConnectionInterface $db = null;
    protected ?string $driverName = 'mysql';
}
