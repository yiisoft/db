<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Pgsql;

use Yiisoft\Db\Pgsql\Tests\QueryTest as PgsqlQueryTest;

/**
 * @group pgsql
 */
final class QueryTest extends PgsqlQueryTest
{
    protected ?string $driverName = 'pgsql';
}
