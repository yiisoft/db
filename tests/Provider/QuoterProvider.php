<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

final class QuoterProvider
{
    /**
     * @return string[][]
     */
    public function columnName(): array
    {
        return [
            ['column', 'column'],
            ['`column`', '`column`'],
            ['[[column]]', '[[column]]'],
            ['{{column}}', '{{column}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function ensureColumnName(): array
    {
        return [
            ['*', '*'],
            ['`*`', '`*`'],
            ['[[*]]', '[*]'],
            ['{{*}}', '{*}'],
            ['table.column', 'column'],
            ['`table`.`column`', '`column`'],
            ['[[table]].[[column]]', 'column'],
            ['{{table}}.{{column}}', '{column}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function ensureNameQuoted(): array
    {
        return [
            ['name', '{name}'],
            ['`name`', '{name}'],
            ['[[name]]', '{name}'],
            ['{{name}}', '{name}'],
            ['table.name', '{table.name}'],
            ['`table`.`name`', '{table.name}'],
            ['[[table]].[[name]]', '{table.name}'],
            ['{{table}}.{{name}}', '{table}.{name}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function simpleColumnName(): array
    {
        return [
            ['column', 'column'],
            ['`column`', '`column`'],
            ['[[column]]', '[[column]]'],
            ['{{column}}', '{{column}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function simpleTableName(): array
    {
        return [
            ['table', 'table'],
            ['`table`', '`table`'],
            ['[[table]]', '[[table]]'],
            ['{{table}}', '{{table}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function tableName(): array
    {
        return [
            ['table', 'table'],
            ['`table`', '`table`'],
            ['(table)', '(table)'],
            ['[[table]]', '[[table]]'],
            ['{{table}}', '{{table}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function tableNameParts(): array
    {
        return [
            ['`schema`.`table`', ['schema', 'table']],
        ];
    }

    /**
     * @return string[][]
     */
    public function unquoteSimpleColumnName(): array
    {
        return [
            ['`column`', 'column'],
            ['[[column]]', '[column]'],
            ['{{column}}', '{column}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function unquoteSimpleTableName(): array
    {
        return [
            ['`table`', 'table'],
            ['[[table]]', '[table]'],
            ['{{table}}', '{table}'],
        ];
    }
}
