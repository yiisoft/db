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
            ['*', '*'],
            ['`*`', '`*`'],
            ['[[*]]', '[[*]]'],
            ['{{*}}', '{{*}}'],
            ['table.column', '`table`.`column`'],
            ['`table`.`column`', '`table`.`column`'],
            ['[[table]].[[column]]', '[[table]].[[column]]'],
            ['{{table}}.{{column}}', '{{column}}'],
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
            ['[[*]]', '[[*]]'],
            ['{{*}}', '{{*}}'],
            ['table.column', 'column'],
            ['`table`.`column`', '`column`'],
            ['[[table]].[[column]]', 'column'],
            ['{{table}}.{{column}}', '{{column}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function ensureNameQuoted(): array
    {
        return [
            ['name', '{{name}}'],
            ['`name`', '{{name}}'],
            ['[[name]]', '{{name}}'],
            ['{{name}}', '{{name}}'],
            ['table.name', '{{table.name}}'],
            ['`table`.`name`', '{{table.name}}'],
            ['[[table]].[[name]]', '{{table.name}}'],
            ['{{table}}.{{name}}', '{{table}}.{{name}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function simpleColumnName(): array
    {
        return [
            ['*', '*'],
            ['`*`', '`*`'],
            ['[[*]]', '`[[*]]`'],
            ['{{*}}', '`{{*}}`'],
            ['table.column', '`table.column`'],
            ['`table`.`column`', '`table`.`column`'],
            ['[[table]].[[column]]', '`[[table]].[[column]]`'],
            ['{{table}}.{{column}}', '`{{table}}.{{column}}`'],
        ];
    }

    /**
     * @return string[][]
     */
    public function simpleTableName(): array
    {
        return [
            ['test', '`test`'],
            ['`test`', '`test`'],
            ['[[test]]', '`[[test]]`'],
            ['{{test}}', '`{{test}}`'],
            ['table.test', '`table.test`'],
            ['`table`.`test`', '`table`.`test`'],
            ['[[table]].[[test]]', '`[[table]].[[test]]`'],
            ['{{table}}.{{test}}', '`{{table}}.{{test}}`'],
        ];
    }

    /**
     * @return string[][]
     */
    public function tableName(): array
    {
        return [
            ['table', '`table`'],
            ['`table`', '`table`'],
            ['(table)', '(table)'],
            ['[[test]]', '`[[test]]`'],
            ['{{test}}', '{{test}}'],
            ['table.column', '`table`.`column`'],
            ['`table`.`column`', '`table`.`column`'],
            ['[[table]].[[column]]', '`[[table]]`.`[[column]]`'],
            ['{{table}}.{{column}}', '{{table}}.{{column}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function tableNameParts(): array
    {
        return [
            ['schema.table', ['schema', 'table']],
            ['`schema`.`table`', ['schema', 'table']],
            ['`table`', ['table']],
            ['table', ['table']],
        ];
    }

    /**
     * @return string[][]
     */
    public function unquoteSimpleColumnName(): array
    {
        return [
            ['*', '*'],
            ['`*`', '*'],
            ['[[*]]', '[[*]]'],
            ['{{*}}', '{{*}}'],
            ['table.column', 'table.column'],
            ['[[table]].[[column]]', '[[table]].[[column]]'],
            ['{{table}}.{{column}}', '{{table}}.{{column}}'],
        ];
    }

    /**
     * @return string[][]
     */
    public function unquoteSimpleTableName(): array
    {
        return [
            ['test', 'test'],
            ['`test`', 'test'],
            ['[[test]]', '[[test]]'],
            ['{{test}}', '{{test}}'],
            ['table.column', 'table.column'],
            ['[[table.column]]', '[[table.column]]'],
            ['{{table.column}}', '{{table.column}}'],
        ];
    }
}
