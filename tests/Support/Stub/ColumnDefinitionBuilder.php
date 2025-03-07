<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\QueryBuilder\AbstractColumnDefinitionBuilder;
use Yiisoft\Db\Schema\Column\ColumnInterface;

final class ColumnDefinitionBuilder extends AbstractColumnDefinitionBuilder
{
    protected const AUTO_INCREMENT_KEYWORD = 'AUTOINCREMENT';

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
    ];

    protected const TYPES_WITH_SCALE = [
        'float',
        'double',
        'decimal',
    ];

    protected function buildCheck(ColumnInterface $column): string
    {
        $check = $column->getCheck();

        if (empty($check)) {
            $columnName = $column->getName();

            if (!empty($columnName) && $column->getType() === ColumnType::JSON) {
                return ' CHECK (json_valid(' . $this->queryBuilder->quoter()->quoteColumnName($columnName) . '))';
            }

            return '';
        }

        return " CHECK ($check)";
    }

    protected function getDbType(ColumnInterface $column): string
    {
        return $column->getDbType() ?? match ($column->getType()) {
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
            ColumnType::STRING => 'varchar(' . ($column->getSize() ?? 255) . ')',
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

    protected function getDefaultUuidExpression(): string
    {
        return 'uuid()';
    }
}
