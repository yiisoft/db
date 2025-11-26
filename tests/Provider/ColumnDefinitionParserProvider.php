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
            ['int UNIQUE', ['type' => 'int', 'unique' => true]],
            ['int(10) UNSIGNED', ['type' => 'int', 'size' => 10, 'unsigned' => true]],
            ['int(10) UNSIGNED NOT NULL', ['type' => 'int', 'size' => 10, 'unsigned' => true, 'notNull' => true]],
            ['int(10) NOT NULL', ['type' => 'int', 'size' => 10, 'notNull' => true]],
            ['text NOT NULL', ['type' => 'text', 'notNull' => true]],
            ['text NULL', ['type' => 'text', 'notNull' => false]],
            ['text COLLATE utf8mb4', ['type' => 'text', 'collation' => 'utf8mb4']],
            ["text COMPRESSION 'LZ4'", ['type' => 'text', 'extra' => "COMPRESSION 'LZ4'"]],
            ['text DEFAULT NULL', ['type' => 'text', 'defaultValueRaw' => 'NULL']],
            ["text DEFAULT 'value'", ['type' => 'text', 'defaultValueRaw' => "'value'"]],
            ['varchar(36) DEFAULT uuid()', ['type' => 'varchar', 'size' => 36, 'defaultValueRaw' => 'uuid()']],
            ['varchar(36) DEFAULT uuid()::varchar(36)', ['type' => 'varchar', 'size' => 36, 'defaultValueRaw' => 'uuid()::varchar(36)']],
            ['int DEFAULT (1 + 2)', ['type' => 'int', 'defaultValueRaw' => '(1 + 2)']],
            ["int COMMENT '''Quoted'' comment'", ['type' => 'int', 'comment' => "'Quoted' comment"]],
            ['int CHECK (value > (1 + 5))', ['type' => 'int', 'check' => 'value > (1 + 5)']],
            ["enum('a','b','c')", ['type' => 'enum', 'values' => ['a', 'b', 'c']]],
            ["enum('a','b','c') NOT NULL", ['type' => 'enum', 'values' => ['a', 'b', 'c'], 'notNull' => true]],
            [
                "enum('hello''world''', 'the ''[feature'']')",
                ['type' => 'enum', 'values' => ["hello'world'", "the '[feature']"]],
            ],
            ['int[]', ['type' => 'int', 'dimension' => 1]],
            ['string(126)[][]', ['type' => 'string', 'size' => 126, 'dimension' => 2]],
        ];
    }
}
