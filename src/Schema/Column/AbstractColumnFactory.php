<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Syntax\ColumnDefinitionParser;

use const PHP_INT_SIZE;

/**
 * The default implementation of the {@see ColumnFactoryInterface}.
 *
 * @psalm-import-type ColumnInfo from ColumnFactoryInterface
 */
abstract class AbstractColumnFactory implements ColumnFactoryInterface
{
    /**
     * The mapping from physical column types (keys) to abstract column types (values).
     *
     * @var string[]
     *
     * @psalm-var array<string, ColumnType::*>
     */
    protected const TYPE_MAP = [];

    public function fromDbType(string $dbType, array $info = []): ColumnSchemaInterface
    {
        $info['dbType'] = $dbType;
        $type = $info['type'] ?? $this->getType($dbType, $info);

        return $this->fromType($type, $info);
    }

    public function fromDefinition(string $definition, array $info = []): ColumnSchemaInterface
    {
        $definitionInfo = $this->columnDefinitionParser()->parse($definition);

        if (isset($info['extra'], $definitionInfo['extra'])) {
            $info['extra'] = $definitionInfo['extra'] . ' ' . $info['extra'];
        }

        /** @var string $type */
        $type = $definitionInfo['type'] ?? '';
        unset($definitionInfo['type']);

        $info += $definitionInfo;

        if ($this->isDbType($type)) {
            return $this->fromDbType($type, $info);
        }

        if ($this->isType($type)) {
            return $this->fromType($type, $info);
        }

        if ($this->isPseudoType($type)) {
            return $this->fromPseudoType($type, $info);
        }

        return $this->fromDbType($type, $info);
    }

    public function fromPseudoType(string $pseudoType, array $info = []): ColumnSchemaInterface
    {
        $info['primaryKey'] = true;
        $info['autoIncrement'] = true;

        if ($pseudoType === PseudoType::UPK || $pseudoType === PseudoType::UBIGPK) {
            $info['unsigned'] = true;
        }

        $type = match ($pseudoType) {
            PseudoType::PK => ColumnType::INTEGER,
            PseudoType::UPK => ColumnType::INTEGER,
            PseudoType::BIGPK => ColumnType::BIGINT,
            PseudoType::UBIGPK => ColumnType::BIGINT,
            PseudoType::UUID_PK => ColumnType::UUID,
            PseudoType::UUID_PK_SEQ => ColumnType::UUID,
        };

        return $this->fromType($type, $info);
    }

    public function fromType(string $type, array $info = []): ColumnSchemaInterface
    {
        unset($info['type']);

        if ($type === ColumnType::ARRAY && empty($info['column']) && !empty($info['dbType'])) {
            $info['column'] = $this->fromDbType($info['dbType'], $info);
        }

        $columnClass = $this->getColumnClass($type, $info);

        return new $columnClass($type, ...$info);
    }

    /**
     * Returns the column definition parser.
     */
    protected function columnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }

    /**
     * @psalm-param ColumnType::* $type
     * @param ColumnInfo $info
     *
     * @psalm-return class-string<ColumnSchemaInterface>
     */
    protected function getColumnClass(string $type, array $info = []): string
    {
        return match ($type) {
            ColumnType::BOOLEAN => BooleanColumnSchema::class,
            ColumnType::BIT => BitColumnSchema::class,
            ColumnType::TINYINT => IntegerColumnSchema::class,
            ColumnType::SMALLINT => IntegerColumnSchema::class,
            ColumnType::INTEGER => PHP_INT_SIZE !== 8 && !empty($info['unsigned'])
                ? BigIntColumnSchema::class
                : IntegerColumnSchema::class,
            ColumnType::BIGINT => PHP_INT_SIZE !== 8 || !empty($info['unsigned'])
                ? BigIntColumnSchema::class
                : IntegerColumnSchema::class,
            ColumnType::DECIMAL => DoubleColumnSchema::class,
            ColumnType::FLOAT => DoubleColumnSchema::class,
            ColumnType::DOUBLE => DoubleColumnSchema::class,
            ColumnType::BINARY => BinaryColumnSchema::class,
            ColumnType::ARRAY => ArrayColumnSchema::class,
            ColumnType::STRUCTURED => StructuredColumnSchema::class,
            ColumnType::JSON => JsonColumnSchema::class,
            default => StringColumnSchema::class,
        };
    }

    /**
     * Get the abstract database type for a database column type.
     *
     * @param string $dbType The database column type.
     * @param array $info The column information.
     *
     * @return string The abstract database type.
     *
     * @psalm-param ColumnInfo $info
     * @psalm-return ColumnType::*
     */
    protected function getType(string $dbType, array $info = []): string
    {
        return static::TYPE_MAP[$dbType] ?? ColumnType::STRING;
    }

    /**
     * Checks if the column type is a database type.
     */
    protected function isDbType(string $dbType): bool
    {
        return isset(static::TYPE_MAP[$dbType]);
    }

    /**
     * Checks if the column type is a pseudo-type.
     *
     * @psalm-assert-if-true PseudoType::* $pseudoType
     */
    protected function isPseudoType(string $pseudoType): bool
    {
        return match ($pseudoType) {
            PseudoType::PK,
            PseudoType::UPK,
            PseudoType::BIGPK,
            PseudoType::UBIGPK,
            PseudoType::UUID_PK,
            PseudoType::UUID_PK_SEQ => true,
            default => false,
        };
    }

    /**
     * Checks if the column type is an abstract type.
     *
     * @psalm-assert-if-true ColumnType::* $type
     */
    protected function isType(string $type): bool
    {
        return match ($type) {
            ColumnType::BOOLEAN,
            ColumnType::BIT,
            ColumnType::TINYINT,
            ColumnType::SMALLINT,
            ColumnType::INTEGER,
            ColumnType::BIGINT,
            ColumnType::FLOAT,
            ColumnType::DOUBLE,
            ColumnType::DECIMAL,
            ColumnType::MONEY,
            ColumnType::CHAR,
            ColumnType::STRING,
            ColumnType::TEXT,
            ColumnType::BINARY,
            ColumnType::UUID,
            ColumnType::DATETIME,
            ColumnType::TIMESTAMP,
            ColumnType::DATE,
            ColumnType::TIME,
            ColumnType::ARRAY,
            ColumnType::STRUCTURED,
            ColumnType::JSON => true,
            default => false,
        };
    }
}
