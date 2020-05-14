<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Pgsql;

use Yiisoft\Db\Tests\DataReaderProviderTest as AbstractDataReaderProviderTest;

/**
 * @group pgsql
 */
final class DataReaderProviderTest extends AbstractDataReaderProviderTest
{
    protected ?string $driverName = 'pgsql';
}
