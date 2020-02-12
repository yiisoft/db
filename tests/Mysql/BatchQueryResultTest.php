<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Tests\BatchQueryResultTest as AbstractBatchQueryResultTest;

final class BatchQueryResultTest extends AbstractBatchQueryResultTest
{
    public ?string $driverName = 'mysql';
}
