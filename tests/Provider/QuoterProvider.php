<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Expression\Expression;

class QuoterProvider
{
    /**
     * @return string[][]
     */
    public static function columnNames(): array
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
    public static function ensureColumnName(): array
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
    public static function ensureNameQuoted(): array
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
    public static function simpleColumnNames(): array
    {
        return [
            ['*', '*', '*'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function simpleTableNames(): array
    {
        return [
            ['test', 'test'],
            ['(test)', '(test)'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function rawTableNames(): array
    {
        return [
            ['table', 'table'],
            ['"table"', '"table"'],
            ['public.table', 'public.table'],
            ['{{table}}', 'table'],
            ['{{public}}.{{table}}', 'public.table'],
            ['{{%table}}', 'yii_table', 'yii_'],
            ['{{public}}.{{%table}}', 'public.yii_table', 'yii_'],
        ];
    }

    /**
     * @return string[][]
     */
    public static function tableNameParts(): array
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

    public static function tablesNameDataProvider(): array
    {
        return [
            [['customer'], '', ['{{customer}}' => '{{customer}}']],
            [['profile AS "prf"'], '', ['{{prf}}' => '{{profile}}']],
            [['mainframe as400'], '', ['{{as400}}' => '{{mainframe}}']],
            [
                ['x' => new Expression('(SELECT id FROM user)')],
                '',
                ['{{x}}' => new Expression('(SELECT id FROM user)')],
            ],
        ];
    }
}
