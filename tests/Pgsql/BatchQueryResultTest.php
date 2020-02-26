<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Pgsql;

use Yiisoft\Db\Tests\BatchQueryResultTest as AbstractBatchQueryResultTest;

/**
 * @group pgsql
 */
final class BatchQueryResultTest extends AbstractBatchQueryResultTest
{
    protected ?string $driverName = 'pgsql';
}
