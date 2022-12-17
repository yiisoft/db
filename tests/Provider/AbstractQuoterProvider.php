<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

abstract class AbstractQuoterProvider
{
    /**
     * @return string[][]
     */
    public function columnNames(): array
    {
        return [
            ['*', '*'],
            ['(*)', '(*)'],
            ['[[*]]', '[[*]]'],
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
    public function simpleColumnNames(): array
    {
        return [
            ['*', '*', '*'],
        ];
    }

    /**
     * @return string[][]
     */
    public function simpleTableNames(): array
    {
        return [
            ['test', 'test'],
        ];
    }

    /**
     * @return string[][]
     */
    public function tableNameParts(): array
    {
        return [
            ['animal', 'animal',],
            ['dbo.animal', 'animal', 'dbo'],
            ['[dbo].[animal]', 'animal', 'dbo'],
            ['[other].[animal2]', 'animal2', 'other'],
            ['other.[animal2]', 'animal2', 'other'],
            ['other.animal2', 'animal2', 'other'],
        ];
    }
}
