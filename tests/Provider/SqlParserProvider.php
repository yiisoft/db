<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

class SqlParserProvider
{
    public static function getNextPlaceholder(): array
    {
        return [
            [
                'SELECT * FROM {{customer}}',
                null,
                null,
            ],
            [
                'SELECT * FROM {{customer}} WHERE name = ::name',
                null,
                null,
            ],
            [
                'SELECT * FROM {{customer}} WHERE name = :name',
                ':name',
                40,
            ],
            [
                'SELECT * FROM {{customer}} WHERE name = :name AND age = :age',
                ':name',
                40,
            ],
            [
                "SELECT * FROM {{customer}} WHERE name = ':name' AND age = :age",
                ':age',
                58,
            ],
            [
                "SELECT * FROM {{customer}} WHERE name = CONCAT(':name', ':surname') AND age = :age",
                ':age',
                78,
            ],
            [
                '[[name]] = :name',
                ':name',
                11,
            ],
            [
                ':name_0',
                ':name_0',
                0,
            ],
            [
                "name = ':na''me' AND age = :age",
                ':age',
                27,
            ],
            [
                '":field" = :name AND age = :age',
                ':name',
                11,
            ],
            [
                '":fie""ld" = :name AND age = :age',
                ':name',
                13,
            ],
            [
                '":field" = \':name\' AND ":age" = :age',
                ':age',
                32,
            ],
            [
                '":field" = CONCAT(\':name\', \':surname\') AND ":age" = :age',
                ':age',
                52,
            ],
            [
                <<<SQL
                SELECT * FROM {{customer}} -- :comment
                WHERE 2-1 AND name = :name
                SQL,
                ':name',
                60,
            ],
            [
                <<<SQL
                SELECT * FROM {{customer}}
                /*
                * :comment
                */
                WHERE 2/1 AND name = :name
                SQL,
                ':name',
                65,
            ],
        ];
    }

    public static function getAllPlaceholders(): array
    {
        return [
            [
                'SELECT * FROM {{customer}} WHERE name = :name',
                [':name'],
                [40],
            ],
            [
                'SELECT * FROM {{customer}} WHERE name = :name AND age = :age',
                [':name', ':age'],
                [40, 56],
            ],
            [
                "SELECT * FROM {{customer}} WHERE name = ':name' AND age = :age",
                [':age'],
                [58],
            ],
            [
                ':name:surname',
                [':name', ':surname'],
                [0, 5],
            ],
            [
                '":field" = :name AND ":filed2" = :age',
                [':name', ':age'],
                [11, 33],
            ],
        ];
    }
}
