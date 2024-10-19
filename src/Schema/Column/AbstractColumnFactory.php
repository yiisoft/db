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
    abstract protected function getType(string $dbType, array $info = []): string;

    /**
     * Checks if the column type is a database type.
     */
    abstract protected function isDbType(string $dbType): bool;

    public function fromDbType(string $dbType, array $info = []): ColumnSchemaInterface
    {
        unset($info['dbType']);
        $type = $info['type'] ?? $this->getType($dbType, $info);
        unset($info['type']);
        $info['dbType'] = $dbType;

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
            unset($info['dbType']);
            return $this->fromDbType($type, $info);
        }

        if ($this->isType($type)) {
            unset($info['type']);
            return $this->fromType($type, $info);
        }

        if ($this->isPseudoType($type)) {
            return $this->fromPseudoType($type, $info);
        }

        unset($info['dbType']);
        return $this->fromDbType($type, $info);
    }

    /**
     * @psalm-suppress MixedArgument
     * @psalm-suppress InvalidArgument
     * @psalm-suppress InvalidNamedArgument
     */
    public function fromPseudoType(string $pseudoType, array $info = []): ColumnSchemaInterface
    {
        $info['primaryKey'] = true;
        $info['autoIncrement'] = true;

        return match ($pseudoType) {
            PseudoType::PK => new IntegerColumnSchema(ColumnType::INTEGER, ...$info),
            PseudoType::UPK => new IntegerColumnSchema(ColumnType::INTEGER, ...[...$info, 'unsigned' => true]),
            PseudoType::BIGPK => PHP_INT_SIZE !== 8
                ? new BigIntColumnSchema(ColumnType::BIGINT, ...$info)
                : new IntegerColumnSchema(ColumnType::BIGINT, ...$info),
            PseudoType::UBIGPK => new BigIntColumnSchema(ColumnType::BIGINT, ...[...$info, 'unsigned' => true]),
            PseudoType::UUID_PK => new StringColumnSchema(ColumnType::UUID, ...$info),
            PseudoType::UUID_PK_SEQ => new StringColumnSchema(ColumnType::UUID, ...$info),
        };
    }

    /**
     * @psalm-suppress InvalidNamedArgument
     */
    public function fromType(string $type, array $info = []): ColumnSchemaInterface
    {
        return match ($type) {
            ColumnType::BOOLEAN => new BooleanColumnSchema($type, ...$info),
            ColumnType::BIT => new BitColumnSchema($type, ...$info),
            ColumnType::TINYINT => new IntegerColumnSchema($type, ...$info),
            ColumnType::SMALLINT => new IntegerColumnSchema($type, ...$info),
            ColumnType::INTEGER => PHP_INT_SIZE !== 8 && !empty($info['unsigned'])
                ? new BigIntColumnSchema($type, ...$info)
                : new IntegerColumnSchema($type, ...$info),
            ColumnType::BIGINT => PHP_INT_SIZE !== 8 || !empty($info['unsigned'])
                ? new BigIntColumnSchema($type, ...$info)
                : new IntegerColumnSchema($type, ...$info),
            ColumnType::DECIMAL => new DoubleColumnSchema($type, ...$info),
            ColumnType::FLOAT => new DoubleColumnSchema($type, ...$info),
            ColumnType::DOUBLE => new DoubleColumnSchema($type, ...$info),
            ColumnType::BINARY => new BinaryColumnSchema($type, ...$info),
            ColumnType::STRUCTURED => new StructuredColumnSchema($type, ...$info),
            ColumnType::JSON => new JsonColumnSchema($type, ...$info),
            default => new StringColumnSchema($type, ...$info),
        };
    }

    /**
     * Returns the column definition parser.
     */
    protected function columnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
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
