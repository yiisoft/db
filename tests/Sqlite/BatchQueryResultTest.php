<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite;

use Yiisoft\Db\Tests\BatchQueryResultTest as AbstractBatchQueryResultTest;

/**
 * @group sqlite
 */
final class BatchQueryResultTest extends AbstractBatchQueryResultTest
{
    protected ?string $driverName = 'sqlite';
}
