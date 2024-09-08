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
 * @psalm-import-type ColumnInfo from ColumnSchemaInterface
 * @psalm-suppress MixedArgumentTypeCoercion
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
        $info['db_type'] = $dbType;
        $type = $info['type'] ?? $this->getType($dbType, $info);

        return $this->fromType($type, $info);
    }

    public function fromDefinition(string $definition, array $info = []): ColumnSchemaInterface
    {
        $definitionInfo = $this->columnDefinitionParser()->parse($definition);

        if (isset($info['extra'], $definitionInfo['extra'])) {
            $info['extra'] = $definitionInfo['extra'] . ' ' . $info['extra'];
        }

        /** @var string $dbType */
        $dbType = $definitionInfo['db_type'] ?? '';
        unset($definitionInfo['db_type']);

        $info += $definitionInfo;

        if ($this->isDbType($dbType)) {
            return $this->fromDbType($dbType, $info);
        }

        if ($this->isType($dbType)) {
            return $this->fromType($dbType, $info);
        }

        if ($this->isPseudoType($dbType)) {
            return $this->fromPseudoType($dbType, $info);
        }

        return $this->fromDbType($dbType, $info);
    }

    public function fromPseudoType(string $pseudoType, array $info = []): ColumnSchemaInterface
    {
        return match ($pseudoType) {
            PseudoType::PK => ColumnBuilder::primaryKey()->load($info),
            PseudoType::UPK => ColumnBuilder::primaryKey()->unsigned()->load($info),
            PseudoType::BIGPK => ColumnBuilder::bigPrimaryKey()->load($info),
            PseudoType::UBIGPK => ColumnBuilder::bigPrimaryKey()->unsigned()->load($info),
            PseudoType::UUID_PK => ColumnBuilder::uuidPrimaryKey()->load($info),
            PseudoType::UUID_PK_SEQ => ColumnBuilder::uuidPrimaryKey(true)->load($info),
        };
    }

    public function fromType(string $type, array $info = []): ColumnSchemaInterface
    {
        $column = match ($type) {
            ColumnType::BOOLEAN => new BooleanColumnSchema($type),
            ColumnType::BIT => new BitColumnSchema($type),
            ColumnType::TINYINT => new IntegerColumnSchema($type),
            ColumnType::SMALLINT => new IntegerColumnSchema($type),
            ColumnType::INTEGER => PHP_INT_SIZE !== 8 && !empty($info['unsigned'])
                ? new BigIntColumnSchema($type)
                : new IntegerColumnSchema($type),
            ColumnType::BIGINT => PHP_INT_SIZE !== 8 || !empty($info['unsigned'])
                ? new BigIntColumnSchema($type)
                : new IntegerColumnSchema($type),
            ColumnType::DECIMAL => new DoubleColumnSchema($type),
            ColumnType::FLOAT => new DoubleColumnSchema($type),
            ColumnType::DOUBLE => new DoubleColumnSchema($type),
            ColumnType::BINARY => new BinaryColumnSchema($type),
            ColumnType::JSON => new JsonColumnSchema($type),
            default => new StringColumnSchema($type),
        };

        return $column->load($info);
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
            ColumnType::JSON => true,
            default => false,
        };
    }
}
