<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Tests\BatchQueryResultTest as AbstractBatchQueryResultTest;

/**
 * @group mysql
 */
final class BatchQueryResultTest extends AbstractBatchQueryResultTest
{
    protected ?string $driverName = 'mysql';
}
