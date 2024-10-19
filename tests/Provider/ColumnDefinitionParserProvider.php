<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

class ColumnDefinitionParserProvider
{
    public static function parse(): array
    {
        return [
            ['', ['type' => '']],
            ['int', ['type' => 'int']],
            ['int(10)', ['type' => 'int', 'size' => 10]],
            ['int UNSIGNED', ['type' => 'int', 'unsigned' => true]],
            ['int(10) UNSIGNED', ['type' => 'int', 'size' => 10, 'unsigned' => true]],
            ['int(10) UNSIGNED NOT NULL', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'extra' => 'NOT NULL']],
            ['int(10) NOT NULL', ['type' => 'int', 'size' => 10, 'extra' => 'NOT NULL']],
            ['text NOT NULL', ['type' => 'text', 'extra' => 'NOT NULL']],
            ["enum('a','b','c')", ['type' => 'enum', 'enumValues' => ['a', 'b', 'c']]],
            ["enum('a','b','c') NOT NULL", ['type' => 'enum', 'enumValues' => ['a', 'b', 'c'], 'extra' => 'NOT NULL']],
        ];
    }
}
