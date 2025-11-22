<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Yiisoft\Db\Schema\Column\EnumColumn;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

abstract class CommonEnumColumnTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        $this->executeStatements(...$this->dropDatabaseObjectsStatements());
        $this->executeStatements(...$this->createDatabaseObjectsStatements());
    }

    protected function tearDown(): void
    {
        $this->executeStatements(...$this->dropDatabaseObjectsStatements());
    }

    public function testSchema(): void
    {
        $db = $this->getSharedConnection();

        $column = $db->getTableSchema('tbl_enum')->getColumn('status');

        $this->assertInstanceOf(EnumColumn::class, $column, 'Column class is "' . $column::class . '"');
        $this->assertSame(
            ['active', 'unactive', 'pending'],
            $column->getEnumValues(),
        );
    }

    public function testInsertAndQuery(): void
    {
        $db = $this->getSharedConnection();

        $db->createCommand()->insertBatch('tbl_enum', [
            ['id' => 1, 'status' => 'active'],
            ['id' => 2, 'status' => 'pending'],
            ['id' => 3, 'status' => 'pending'],
            ['id' => 4, 'status' => 'unactive'],
        ])->execute();

        $rows = $db->select('status')->from('tbl_enum')->orderBy('id ASC')->column();

        $this->assertSame(
            ['active', 'pending', 'pending', 'unactive'],
            $rows,
        );
    }

    /**
     * SQL statements for create all database objects needed for the test.
     *
     * Table "tbl_enum" with columns:
     *  - column "id" of type integer;
     *  - enum column "status" that accepts values 'active', 'unactive', 'pending'.
     *
     * @return string[]
     */
    abstract protected function createDatabaseObjectsStatements(): array;

    /**
     * SQL statements for remove all database objects created for the test.
     * Take account that object can be absent.
     *
     * @return string[]
     */
    abstract protected function dropDatabaseObjectsStatements(): array;
}
