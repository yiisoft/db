<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\Attributes\TestWith;
use Yiisoft\Db\Schema\Column\EnumColumn;
use Yiisoft\Db\Schema\TableSchemaInterface;
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
        $this->assertEqualsCanonicalizing(
            ['active', 'unactive', 'pending'],
            $column->getValues(),
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

    #[TestWith([['active', 'inactive', 'pending']])]
    #[TestWith([['[one]', 'the [two]', 'the [three] to']])]
    #[TestWith([["hello''world''", "the '[feature']"]])]
    public function testCreateTable(array $items): void
    {
        $this->dropTable('test_enum_table');

        $db = $this->getSharedConnection();
        $columnBuilder = $db->getColumnBuilderClass();

        $db->createCommand()
            ->createTable(
                'test_enum_table',
                [
                    'id' => $columnBuilder::integer(),
                    'status' => $columnBuilder::enum($items),
                ],
            )
            ->execute();

        $tableSchema = $db->getTableSchema('test_enum_table');
        $this->assertInstanceOf(TableSchemaInterface::class, $tableSchema);

        $columns = $tableSchema->getColumns();
        $this->assertEqualsCanonicalizing(['id', 'status'], array_keys($columns));

        $column = $columns['status'];
        $this->assertInstanceOf(EnumColumn::class, $column, $column::class);
        $this->assertEqualsCanonicalizing($items, $column->getValues());

        $this->dropTable('test_enum_table');
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
