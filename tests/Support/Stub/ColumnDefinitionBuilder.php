<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\QueryBuilder\AbstractColumnDefinitionBuilder;
use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

final class ColumnDefinitionBuilder extends AbstractColumnDefinitionBuilder
{
    protected const AUTO_INCREMENT_KEYWORD = 'AUTO_INCREMENT';

    protected const GENERATE_UUID_EXPRESSION = 'uuid()';

    protected const TYPES_WITH_SIZE = [
        'bit',
        'tinyint',
        'smallint',
        'integer',
        'bigint',
        'float',
        'double',
        'decimal',
        'char',
        'varchar',
        'text',
        'binary',
        'datetime',
        'timestamp',
        'time',

//        'bool',
//        'boolean',
//        'date',
//        'json',
    ];

    protected const TYPES_WITH_SCALE = [
        'float',
        'double',
        'decimal',
    ];

    protected function getDbType(ColumnSchemaInterface $column): string
    {
        return match ($column->getType()) {
            ColumnType::BOOLEAN => 'boolean',
            ColumnType::BIT => 'bit',
            ColumnType::TINYINT => 'tinyint',
            ColumnType::SMALLINT => 'smallint',
            ColumnType::INTEGER => 'integer',
            ColumnType::BIGINT => 'bigint',
            ColumnType::FLOAT => 'float',
            ColumnType::DOUBLE => 'double',
            ColumnType::DECIMAL => 'decimal',
            ColumnType::MONEY => 'money',
            ColumnType::CHAR => 'char',
            ColumnType::STRING => 'varchar',
            ColumnType::TEXT => 'text',
            ColumnType::BINARY => 'binary',
            ColumnType::UUID => 'uuid',
            ColumnType::DATETIME => 'datetime',
            ColumnType::TIMESTAMP => 'timestamp',
            ColumnType::DATE => 'date',
            ColumnType::TIME => 'time',
            ColumnType::ARRAY => 'json',
            ColumnType::STRUCTURED => 'json',
            ColumnType::JSON => 'json',
            default => 'varchar',
        };
    }
}
