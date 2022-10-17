<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Schema\ColumnSchema;
use Yiisoft\Db\Schema\Schema;

use function is_object;

abstract class AbstractColumnSchemaTest extends TestCase
{
    public function testColumnSchemaDbTypecastWithEmptyCharType(): void
    {
        $columnSchema = new ColumnSchema();
        $columnSchema->setType(Schema::TYPE_CHAR);

        $this->assertSame('', $columnSchema->dbTypecast(''));
    }

    public function testColumnSchema(): void
    {
        $columns = $this->getExpectedColumns();

        $db = $this->getConnection();

        $schema = $db->getSchema();
        $table = $schema->getTableSchema('type', true);

        $this->assertNotNull($table);

        $expectedColNames = array_keys($columns);
        sort($expectedColNames);
        $colNames = $table->getColumnNames();
        sort($colNames);

        $this->assertSame($expectedColNames, $colNames);

        foreach ($table->getColumns() as $name => $column) {
            $expected = $columns[$name];
            $this->assertSame(
                $expected['dbType'],
                $column->getDbType(),
                "dbType of column $name does not match. type is {$column->getType()}, dbType is {$column->getDbType()}."
            );
            $this->assertSame(
                $expected['phpType'],
                $column->getPhpType(),
                "phpType of column $name does not match. type is {$column->getType()}, dbType is {$column->getDbType()}."
            );
            $this->assertSame($expected['type'], $column->getType(), "type of column $name does not match.");
            $this->assertSame(
                $expected['allowNull'],
                $column->isAllowNull(),
                "allowNull of column $name does not match."
            );
            $this->assertSame(
                $expected['autoIncrement'],
                $column->isAutoIncrement(),
                "autoIncrement of column $name does not match."
            );
            $this->assertSame(
                $expected['enumValues'],
                $column->getEnumValues(),
                "enumValues of column $name does not match."
            );
            $this->assertSame($expected['size'], $column->getSize(), "size of column $name does not match.");
            $this->assertSame(
                $expected['precision'],
                $column->getPrecision(),
                "precision of column $name does not match."
            );
            $this->assertSame($expected['scale'], $column->getScale(), "scale of column $name does not match.");
            if (is_object($expected['defaultValue'])) {
                $this->assertIsObject(
                    $column->getDefaultValue(),
                    "defaultValue of column $name is expected to be an object but it is not."
                );
                $this->assertEquals(
                    (string) $expected['defaultValue'],
                    (string) $column->getDefaultValue(),
                    "defaultValue of column $name does not match."
                );
            } else {
                $this->assertEquals(
                    $expected['defaultValue'],
                    $column->getDefaultValue(),
                    "defaultValue of column $name does not match."
                );
            }
            /* Pgsql only */
            if (isset($expected['dimension'])) {
                /** @psalm-suppress UndefinedMethod */
                $this->assertSame(
                    $expected['dimension'],
                    $column->getDimension(),
                    "dimension of column $name does not match"
                );
            }
        }
    }
}
