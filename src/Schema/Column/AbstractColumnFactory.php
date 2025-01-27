<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Syntax\ColumnDefinitionParser;

use function array_diff_key;
use function is_numeric;
use function preg_match;
use function str_replace;
use function substr;

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

    public function fromDbType(string $dbType, array $info = []): ColumnInterface
    {
        $info['dbType'] = $dbType;
        $type = $info['type'] ?? $this->getType($dbType, $info);

        return $this->fromType($type, $info);
    }

    public function fromDefinition(string $definition, array $info = []): ColumnInterface
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

    public function fromPseudoType(string $pseudoType, array $info = []): ColumnInterface
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

    public function fromType(string $type, array $info = []): ColumnInterface
    {
        unset($info['type']);

        if ($type === ColumnType::ARRAY && empty($info['column']) && !empty($info['dbType'])) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $info['column'] = $this->fromDbType(
                $info['dbType'],
                array_diff_key($info, ['dimension' => 1, 'defaultValueRaw' => 1])
            );
        }

        $columnClass = $this->getColumnClass($type, $info);

        $column = new $columnClass($type, ...$info);

        if (isset($info['defaultValueRaw'])) {
            $column->defaultValue($this->normalizeDefaultValue($info['defaultValueRaw'], $column));
        }

        return $column;
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
     * @psalm-return class-string<ColumnInterface>
     */
    protected function getColumnClass(string $type, array $info = []): string
    {
        return match ($type) {
            ColumnType::BOOLEAN => BooleanColumn::class,
            ColumnType::BIT => BitColumn::class,
            ColumnType::TINYINT => IntegerColumn::class,
            ColumnType::SMALLINT => IntegerColumn::class,
            ColumnType::INTEGER => PHP_INT_SIZE !== 8 && !empty($info['unsigned'])
                ? BigIntColumn::class
                : IntegerColumn::class,
            ColumnType::BIGINT => PHP_INT_SIZE !== 8 || !empty($info['unsigned'])
                ? BigIntColumn::class
                : IntegerColumn::class,
            ColumnType::DECIMAL => DoubleColumn::class,
            ColumnType::FLOAT => DoubleColumn::class,
            ColumnType::DOUBLE => DoubleColumn::class,
            ColumnType::BINARY => BinaryColumn::class,
            ColumnType::ARRAY => ArrayColumn::class,
            ColumnType::STRUCTURED => StructuredColumn::class,
            ColumnType::JSON => JsonColumn::class,
            default => StringColumn::class,
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

    /**
     * Converts column's default value according to {@see ColumnInterface::getPhpType()} after retrieval from the
     * database.
     *
     * @param string|null $defaultValue The default value retrieved from the database.
     * @param ColumnInterface $column The column object.
     *
     * @return mixed The normalized default value.
     */
    protected function normalizeDefaultValue(string|null $defaultValue, ColumnInterface $column): mixed
    {
        if (
            $defaultValue === null
            || $defaultValue === ''
            || $column->isPrimaryKey()
            || $column->isComputed()
            || preg_match('/^\(?NULL\b/i', $defaultValue) === 1
        ) {
            return null;
        }

        return $this->normalizeNotNullDefaultValue($defaultValue, $column);
    }

    /**
     * Converts a not null default value according to {@see ColumnInterface::getPhpType()}.
     */
    protected function normalizeNotNullDefaultValue(string $defaultValue, ColumnInterface $column): mixed
    {
        $value = $defaultValue;

        if ($value[0] === '(' && $value[-1] === ')') {
            $value = substr($value, 1, -1);
        }

        if (is_numeric($value)) {
            return $column->phpTypecast($value);
        }

        if ($value[0] === "'" && $value[-1] === "'") {
            $value = substr($value, 1, -1);
            $value = str_replace("''", "'", $value);

            return $column->phpTypecast($value);
        }

        return match ($value) {
            'true' => true,
            'false' => false,
            default => new Expression($defaultValue),
        };
    }
}
