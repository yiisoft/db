<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Tests\Support\DbHelper;
use Yiisoft\Db\Tests\Support\TestTrait;

final class QueryBuilderProvider
{
    use TestTrait;

    public function buildFrom(): array
    {
        $db = $this->getConnection();
        $driver = $db->getName();

        return [
            [
                'table1',
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[table1]]
                    SQL,
                    $driver,
                ),
            ],
            [
                ['table1'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[table1]]
                    SQL,
                    $driver,
                ),
            ],
            [
                new Expression('table2'),
                <<<SQL
                SELECT * FROM table2
                SQL,
            ],
            [
                [new Expression('table2')],
                <<<SQL
                SELECT * FROM table2
                SQL,
            ],
            [
                ['alias' => 'table3'],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM [[table3]] [[alias]]
                    SQL,
                    $driver,
                ),
            ],
            [
                ['alias' => new Expression('table4')],
                <<<SQL
                SELECT * FROM table4 [alias]
                SQL,
            ],
            [
                ['alias' => new Expression('func(:param1, :param2)', ['param1' => 'A', 'param2' => 'B'])],
                DbHelper::replaceQuotes(
                    <<<SQL
                    SELECT * FROM func(:param1, :param2) [[alias]]
                    SQL,
                    $driver,
                ),
                ['param1' => 'A', 'param2' => 'B'],
            ],
        ];
    }
}
