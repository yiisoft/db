<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\TableName;

final class TableNameTest extends TestCase
{
    private const PREFIX = 'prefix_';

    public function tablesNameDataProvider(): array
    {
        return [
            'simple table name' => [
                'simpleTable',
                'simpleTable',
            ],
            'with schema' => [
                'schemaName.simpleTable',
                'simpleTable',
                'schemaName',
            ],
            'with schema & catalog' => [
                'catalogName.schemaName.simpleTable',
                'simpleTable',
                'schemaName',
                'catalogName',
            ],
            'with serverName & schema & catalog' => ['serverName.catalogName.schemaName.simpleTable', 'simpleTable', 'schemaName', 'catalogName', 'serverName'],
        ];
    }

    public function tablesNameDataWithPrefixProvider(): array
    {
        return [
            'simple table name' => [
                'prefix_simpleTable',
                'simpleTable',
            ],
            'with schema' => [
                'schemaName.prefix_simpleTable',
                'simpleTable',
                'schemaName',
            ],
            'with schema & catalog' => [
                'catalogName.schemaName.prefix_simpleTable',
                'simpleTable',
                'schemaName',
                'catalogName',
            ],
            'with serverName & schema & catalog' => [
                'serverName.catalogName.schemaName.prefix_simpleTable',
                'simpleTable',
                'schemaName',
                'catalogName',
                'serverName',
            ],
        ];
    }

    /**
     * @dataProvider tablesNameDataProvider
     */
    public function testTableName(string $expected, string $tableName, ?string $schemaName = null, ?string $catalogName = null, ?string $serverName = null): void
    {
        $this->assertEquals(
            $expected,
            new TableName($tableName, $schemaName, $catalogName, $serverName)
        );
    }

    /**
     * @dataProvider tablesNameDataWithPrefixProvider
     */
    public function testTableNameWithPrefix(string $expected, string $tableName, ?string $schemaName = null, ?string $catalogName = null, ?string $serverName = null): void
    {
        $this->assertEquals(
            $expected,
            (new TableName($tableName, $schemaName, $catalogName, $serverName))->setPrefix(self::PREFIX)
        );
    }
}
