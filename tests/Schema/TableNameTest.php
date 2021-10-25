<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Schema;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\TableName;

final class TableNameTest extends TestCase
{
    private string $incorrectTableTypeMessage = 'Name of table should be string or instanceof ExpressionInterface';
    private string $incorrectSchemaTypeMessage = 'Schema should be null, string or instanceof ExpressionInterface';

    /**
     * @param $name
     * @param $alias
     * @param $schema
     * @param $hasAlias
     * @param $hasSchema
     *
     * @throws \Yiisoft\Db\Exception\InvalidArgumentException
     *
     * @dataProvider correctTableNames
     */
    public function testCorrectTableName($name, $alias, $schema, $hasAlias, $hasSchema): void
    {
        $tableName = new TableName($name, $alias, $schema);
        $this->assertEquals($hasAlias, $tableName->hasAlias());
        $this->assertEquals($hasSchema, $tableName->hasSchema());
    }

    /**
     * @return array[]
     */
    public function correctTableNames(): array
    {
        return [
            ['table1', 't1', 'schema1', true, true,],
            ['table1', null, 'schema1', false, true,],
            ['table1', '', 'schema1', false, true,],
            ['table1', 't1', null, true, false,],
            ['table1', 't1', '', true, false,],
            ['table1', null , null, false, false,],
            ['table1', '' , '', false, false,],

            [new Expression('table1'), 't1', 'schema1', true, true,],
            [new Expression('table1'), 't1', new Expression('schema1'), true, true,],
        ];
    }

    /**
     * @param $name
     * @param $alias
     * @param $schema
     * @param $hasAlias
     * @param $hasSchema
     *
     * @throws \Yiisoft\Db\Exception\InvalidArgumentException
     *
     * @dataProvider incorrectTableNames
     */
    public function testIncorrectTableName($name, $alias, $schema, string $expectedExceptionMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $tableName = new TableName($name, $alias, $schema);
    }

    /**
     * @return array[]
     */
    public function incorrectTableNames(): array
    {
        return [
            [123, null, null, $this->incorrectTableTypeMessage],
            [123, 't1', 'schema1', $this->incorrectTableTypeMessage],
            [123, 't1', 'schema1', $this->incorrectTableTypeMessage],
            ['ffsdfs', 't1', 234, $this->incorrectSchemaTypeMessage],
            ['ffsdfs', null, 234, $this->incorrectSchemaTypeMessage],
        ];
    }
}
