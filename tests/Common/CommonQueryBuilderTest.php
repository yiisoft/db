<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Yiisoft\Db\Schema\SchemaInterface;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Provider\ColumnTypes;

use function str_replace;
use function str_starts_with;
use function strncmp;
use function substr;

abstract class CommonQueryBuilderTest extends AbstractQueryBuilderTest
{
    public function testCreateTableWithGetColumnTypes(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        if ($db->getTableSchema('column_type_table', true) !== null) {
            $db->createCommand($qb->dropTable('column_type_table'))->execute();
        }

        $columnTypes = (new ColumnTypes($db))->getColumnTypes();
        $columns = [];
        $i = 0;

        foreach ($columnTypes as [$column, $builder, $expected]) {
            if (
                !(
                    strncmp($column, SchemaInterface::TYPE_PK, 2) === 0 ||
                    strncmp($column, SchemaInterface::TYPE_UPK, 3) === 0 ||
                    strncmp($column, SchemaInterface::TYPE_BIGPK, 5) === 0 ||
                    strncmp($column, SchemaInterface::TYPE_UBIGPK, 6) === 0 ||
                    str_starts_with(substr($column, -5), 'FIRST')
                )
            ) {
                $columns['col' . ++$i] = str_replace('CHECK (value', 'CHECK ([[col' . $i . ']]', $column);
            }
        }

        $db->createCommand($qb->createTable('column_type_table', $columns))->execute();

        $this->assertNotEmpty($db->getTableSchema('column_type_table', true));

        $db->close();
    }

    public function testGetColumnType(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $columnTypes = (new ColumnTypes($db))->getColumnTypes();

        foreach ($columnTypes as $item) {
            [$column, $builder, $expected] = $item;

            $driverName = $db->getDriver()->getDriverName();
            if (isset($item[3][$driverName])) {
                $expectedColumnSchemaBuilder = $item[3][$driverName];
            } elseif (isset($item[3]) && !is_array($item[3])) {
                $expectedColumnSchemaBuilder = $item[3];
            } else {
                $expectedColumnSchemaBuilder = $column;
            }

            $this->assertEquals($expectedColumnSchemaBuilder, $builder->__toString());
            $this->assertEquals($expected, $qb->getColumnType($column));
            $this->assertEquals($expected, $qb->getColumnType($builder));
        }

        $db->close();
    }
}
