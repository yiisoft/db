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
            ['test', '[test]'],
            ['[test]', '[test]'],
            ['*', '*'],
        ];
    }

    /**
     * @return string[][]
     */
    public function simpleTableName(): array
    {
        return [
            ['test', '[test]', ],
            ['te`st', '[te`st]', ],
            ['te\'st', '[te\'st]', ],
            ['te"st', '[te"st]', ],
            ['current-table-name', '[current-table-name]'],
            ['[current-table-name]', '[current-table-name]'],
        ];
    }

    /**
     * @return string[][]
     */
    public function tableName(): array
    {
        return [
            ['test', '[test]'],
            ['test.test', '[test].[test]'],
            ['[test]', '[test]'],
            ['[test].[test]', '[test].[test]'],
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

    /**
     * @return string[][]
     */
    public function unquoteSimpleColumnName(): array
    {
        return [
            ['test', 'test'],
            ['[test]', 'test'],
            ['*', '*'],
        ];
    }

    /**
     * @return string[][]
     */
    public function unquoteSimpleTableName(): array
    {
        return [
            ['[test]', 'test'],
            ['[te`st]', 'te`st'],
            ['[te\'st]', 'te\'st'],
            ['[te"st]', 'te"st'],
            ['[current-table-name]', 'current-table-name'],
            ['[current-table-name]', 'current-table-name'],
        ];
    }
}
