<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Mysql;

use Yiisoft\Db\Tests\DataReaderProviderTest as AbstractDataReaderProviderTest;

/**
 * @group mysql
 */
final class DataReaderProviderTest extends AbstractDataReaderProviderTest
{
    protected ?string $driverName = 'mysql';
}
