<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Sqlite;

use Yiisoft\Db\Tests\DataReaderProviderTest as AbstractDataReaderProviderTest;

/**
 * @group sqlite
 */
final class DataReaderProviderTest extends AbstractDataReaderProviderTest
{
    protected ?string $driverName = 'sqlite';
}
