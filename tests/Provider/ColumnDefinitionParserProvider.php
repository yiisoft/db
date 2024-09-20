<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

class ColumnDefinitionParserProvider
{
    public static function parse(): array
    {
        return [
            ['', ['db_type' => '']],
            ['int', ['db_type' => 'int']],
            ['int(10)', ['db_type' => 'int', 'size' => 10]],
            ['int UNSIGNED', ['db_type' => 'int', 'unsigned' => true]],
            ['int(10) UNSIGNED', ['db_type' => 'int', 'size' => 10, 'unsigned' => true]],
            ['int(10) UNSIGNED NOT NULL', ['db_type' => 'int', 'size' => 10, 'unsigned' => true, 'extra' => 'NOT NULL']],
            ['int(10) NOT NULL', ['db_type' => 'int', 'size' => 10, 'extra' => 'NOT NULL']],
            ['text NOT NULL', ['db_type' => 'text', 'extra' => 'NOT NULL']],
            ["enum('a','b','c')", ['db_type' => 'enum', 'enum_values' => ['a', 'b', 'c']]],
            ["enum('a','b','c') NOT NULL", ['db_type' => 'enum', 'enum_values' => ['a', 'b', 'c'], 'extra' => 'NOT NULL']],
        ];
    }
}
