<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Command\DataType;

class CommandPDOProvider
{
    public static function bindParam(): array
    {
        return [
            [
                'id',
                ':id',
                1,
                DataType::STRING,
                null,
                null,
                [
                    'id' => '1',
                    'email' => 'user1@example.com',
                    'name' => 'user1',
                    'address' => 'address1',
                    'status' => '1',
                    'profile_id' => '1',
                ],
            ],
        ];
    }

    public static function bindParamsNonWhere(): array
    {
        return [
            [
                <<<SQL
                SELECT SUBSTR(name, :len) FROM [[customer]] WHERE [[email]] = :email GROUP BY SUBSTR(name, :len)
                SQL,
            ],
            [
                <<<SQL
                SELECT SUBSTR(name, :len) FROM [[customer]] WHERE [[email]] = :email ORDER BY SUBSTR(name, :len)
                SQL,
            ],
            [
                <<<SQL
                SELECT SUBSTR(name, :len) FROM [[customer]] WHERE [[email]] = :email
                SQL,
            ],
        ];
    }
}
