<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Pgsql;

use Yiisoft\Db\Pgsql\Tests\QueryBuilderTest as PgsqlQueryBuilderTest;

/**
 * @group pgsql
 */
final class QueryBuilderTest extends PgsqlQueryBuilderTest
{
    protected ?string $driverName = 'pgsql';
}
