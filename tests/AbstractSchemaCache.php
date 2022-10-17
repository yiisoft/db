<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;

abstract class AbstractSchemaCache extends TestCase
{
    public function testSchemaCache(): void
    {
        $db = $this->getConnection(true);

        $schema = $db->getSchema();
        $schema->schemaCacheEnable(true);
        $noCacheTable = $schema->getTableSchema('type', true);
        $cachedTable = $schema->getTableSchema('type', false);

        $this->assertSame($noCacheTable, $cachedTable);

        $db->createCommand()->renameTable('type', 'type_test');
        $noCacheTable = $schema->getTableSchema('type', true);

        $this->assertNotSame($noCacheTable, $cachedTable);

        $db->createCommand()->renameTable('type_test', 'type');
    }

    /**
     * @depends testSchemaCache
     */
    public function testRefreshTableSchema(): void
    {
        $db = $this->getConnection();

        $schema = $db->getSchema();
        $schema->schemaCacheEnable(true);
        $noCacheTable = $schema->getTableSchema('type', true);
        $schema->refreshTableSchema('type');
        $refreshedTable = $schema->getTableSchema('type', false);

        $this->assertNotSame($noCacheTable, $refreshedTable);
    }
}
