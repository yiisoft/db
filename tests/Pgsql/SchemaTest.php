<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Pgsql;

use Yiisoft\Db\Pgsql\Tests\SchemaTest as PgsqlSchemaTest;

/**
 * @group pgsql
 */
final class SchemaTest extends PgsqlSchemaTest
{
    protected ?string $driverName = 'pgsql';

    protected array $expectedSchemas = [
        'public',
    ];
}
