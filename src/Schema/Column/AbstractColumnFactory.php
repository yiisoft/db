<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Closure;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Syntax\ColumnDefinitionParser;

use function array_diff_key;
use function array_key_exists;
use function array_merge;
use function is_callable;
use function is_numeric;
use function preg_match;
use function str_replace;
use function substr;

use const PHP_INT_SIZE;

/**
 * The default implementation of the {@see ColumnFactoryInterface}.
 *
 * @psalm-import-type ColumnInfo from ColumnFactoryInterface
 *
 * @psalm-type ColumnClassMap = array<ColumnType::*, class-string<ColumnInterface>|Closure(ColumnType::*, ColumnInfo): (class-string<ColumnInterface>|null)>
 * @psalm-type TypeMap = array<string, ColumnType::*|Closure(string, ColumnInfo): (ColumnType::*|null)>
 */
abstract class AbstractColumnFactory implements ColumnFactoryInterface
{
    /**
     * @var string[] The mapping from physical column types (keys) to abstract column types (values).
     *
     * @psalm-var array<string, ColumnType::*>
     */
    protected const TYPE_MAP = [];

    /**
     * @param array $classMap The mapping from abstract column types to the classes implementing them. Where
     * array keys are abstract column types and values are corresponding class names or PHP callable with the following
     * signature: `function (string $type, array &$info): string|null`. The callable should return the class name based
     * on the abstract type and the column information or `null` if the class name cannot be determined.
     * @param array $typeMap The mapping from physical column types to abstract column types. Where array keys
     * are physical column types and values are corresponding abstract column types or PHP callable with the following
     * signature: `function (string $dbType, array &$info): string|null`. The callable should return the abstract type
     * based on the physical type and the column information or `null` if the abstract type cannot be determined.
     *
     * For example:
     *
     * ```php
     * $classMap = [
     *     ColumnType::ARRAY => ArrayLazyColumn::class,
     *     ColumnType::JSON => JsonLazyColumn::class,
     * ];
     *
     * $typeMap = [
     *     'json' => function (string $dbType, array &$info): string|null {
     *         if (str_ends_with($info['name'], '_ids')) {
     *             $info['column'] = new IntegerColumn();
     *             return ColumnType::ARRAY;
     *         }
     *
     *         return null;
     *     },
     * ];
     *
     * $columnFactory = new ColumnFactory($classMap, $typeMap);
     * ```
     *
     * @psalm-param TypeMap $typeMap
     * @psalm-param ColumnClassMap $classMap
     * @psalm-param array<ColumnType::*, ColumnInfo> $classDefaults
     */
    public function __construct(
        protected array $typeMap = [],
        protected array $classMap = [],
        protected array $classDefaults = [],
    ) {
    }

    public function fromDbType(string $dbType, array $info = []): ColumnInterface
    {
        $info['dbType'] = $dbType;
        /** @psalm-var ColumnType::* $type */
        $type = $info['type']
            ?? $this->mapType($this->typeMap, $dbType, $info)
            ?? $this->getType($dbType, $info);

        return $this->fromType($type, $info);
    }

    public function fromDefinition(string $definition, array $info = []): ColumnInterface
    {
        $definitionInfo = $this->columnDefinitionParser()->parse($definition);

        if (isset($info['extra'], $definitionInfo['extra'])) {
            $info['extra'] = $definitionInfo['extra'] . ' ' . $info['extra'];
            unset($definitionInfo['extra']);
        }

        /** @var string $type */
        $type = $definitionInfo['type'] ?? '';
        unset($definitionInfo['type']);

        $info = array_merge($info, $definitionInfo);

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

        if ($type === ColumnType::ARRAY || !empty($info['dimension'])) {
            if (empty($info['column'])) {
                if (!empty($info['dbType']) && $info['dbType'] !== ColumnType::ARRAY) {
                    /** @psalm-suppress ArgumentTypeCoercion */
                    $info['column'] = $this->fromDbType(
                        $info['dbType'],
                        array_diff_key($info, ['dimension' => 1, 'defaultValueRaw' => 1])
                    );
                } elseif ($type !== ColumnType::ARRAY) {
                    /** @psalm-suppress ArgumentTypeCoercion */
                    $info['column'] = $this->fromType(
                        $type,
                        array_diff_key($info, ['dimension' => 1, 'defaultValueRaw' => 1])
                    );
                }
            }

            $type = ColumnType::ARRAY;
        }

        /** @psalm-var class-string<ColumnInterface> $columnClass */
        $columnClass = $this->mapType($this->classMap, $type, $info)
            ?? $this->getColumnClass($type, $info);

        $columnParams = $info + ($this->classDefaults[$type] ?? []);
        $column = new $columnClass($type, ...$columnParams);

        if (array_key_exists('defaultValueRaw', $columnParams)) {
            $column->defaultValue($this->normalizeDefaultValue($columnParams['defaultValueRaw'], $column));
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
     * @psalm-param ColumnInfo $info
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
            ColumnType::TIMESTAMP => DateTimeColumn::class,
            ColumnType::DATETIME => DateTimeColumn::class,
            ColumnType::DATETIMETZ => DateTimeColumn::class,
            ColumnType::TIME => DateTimeColumn::class,
            ColumnType::TIMETZ => DateTimeColumn::class,
            ColumnType::DATE => DateTimeColumn::class,
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
        if (!empty($info['dimension'])) {
            return ColumnType::ARRAY;
        }

        return static::TYPE_MAP[$dbType] ?? ColumnType::STRING;
    }

    /**
     * Checks if the column type is a database type.
     */
    protected function isDbType(string $dbType): bool
    {
        return isset(static::TYPE_MAP[$dbType]) || !($this->isType($dbType) || $this->isPseudoType($dbType));
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
            ColumnType::TIMESTAMP,
            ColumnType::DATETIME,
            ColumnType::DATETIMETZ,
            ColumnType::TIME,
            ColumnType::TIMETZ,
            ColumnType::DATE,
            ColumnType::ARRAY,
            ColumnType::STRUCTURED,
            ColumnType::JSON => true,
            default => isset($this->classMap[$type]),
        };
    }

    /**
     * Maps a type to a value using a mapping array.
     *
     * @param array $map The mapping array.
     * @param string $type The type to map.
     * @param array $info The column information.
     *
     * @return string|null The mapped value or `null` if the type is not corresponding to any value.
     *
     * @psalm-param ColumnInfo $info
     * @psalm-assert ColumnInfo $info
     */
    protected function mapType(array $map, string $type, array &$info = []): string|null
    {
        if (!isset($map[$type])) {
            return null;
        }

        if (is_callable($map[$type])) {
            /** @var string|null */
            return $map[$type]($type, $info);
        }

        /** @var string */
        return $map[$type];
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
