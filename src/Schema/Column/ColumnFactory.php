<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Schema\SchemaInterface;

use function explode;
use function in_array;
use function preg_match;
use function preg_match_all;
use function str_contains;
use function str_ireplace;
use function strlen;
use function strtolower;
use function substr;
use function trim;

use const PHP_INT_SIZE;

/**
 * @psalm-param array<string, class-string<ColumnInterface>> $fromDbType
 * @psalm-param array<string, class-string<ColumnInterface>> $fromType
 */
class ColumnFactory implements ColumnFactoryInterface
{
    private const BUILDERS = [
        'pk', 'upk', 'bigpk', 'ubigpk', 'uuidpk', 'uuidpkseq',
    ];

    private const TYPES = [
        SchemaInterface::TYPE_UUID,
        SchemaInterface::TYPE_CHAR,
        SchemaInterface::TYPE_STRING,
        SchemaInterface::TYPE_TEXT,
        SchemaInterface::TYPE_BINARY,
        SchemaInterface::TYPE_BIT,
        SchemaInterface::TYPE_BOOLEAN,
        SchemaInterface::TYPE_TINYINT,
        SchemaInterface::TYPE_SMALLINT,
        SchemaInterface::TYPE_INTEGER,
        SchemaInterface::TYPE_BIGINT,
        SchemaInterface::TYPE_FLOAT,
        SchemaInterface::TYPE_DOUBLE,
        SchemaInterface::TYPE_DECIMAL,
        SchemaInterface::TYPE_MONEY,
        SchemaInterface::TYPE_DATETIME,
        SchemaInterface::TYPE_TIMESTAMP,
        SchemaInterface::TYPE_TIME,
        SchemaInterface::TYPE_DATE,
        SchemaInterface::TYPE_JSON,
        SchemaInterface::TYPE_ARRAY,
        SchemaInterface::TYPE_COMPOSITE,
    ];

    public function __construct(
        private string $columnBuilderClass = ColumnBuilder::class,
        private array $fromDbType = [],
        private array $fromType = [],
    ) {
    }

    public function fromDbType(string $dbType, array $info = []): ColumnInterface
    {
        $info['db_type'] = $dbType;
        $type = $info['type'] ?? $this->getType($dbType);

        if (isset($this->fromDbType[$dbType])) {
            $phpType = $info['php_type'] ?? $this->getPhpType($type);
            return (new $this->fromDbType[$dbType]($type, $phpType))->load($info);
        }

        return $this->fromType($type, $info);
    }

    public function fromDefinition(string $definition, array $info = []): ColumnInterface
    {
        preg_match('/^(\w*)(?:\(([^)]+)\))?\s*/', $definition, $matches);

        $dbType = strtolower($matches[1]);

        if (isset($matches[2])) {
            if ($dbType === 'enum') {
                preg_match_all("/'([^']*)'/", $matches[2], $values);

                $info['values'] = $values[1];
            } else {
                $values = explode(',', $matches[2]);
                $info['size'] = (int) $values[0];

                if (isset($values[1])) {
                    $info['scale'] = (int) $values[1];
                }
            }
        }

        $extra = substr($definition, strlen($matches[0]));

        if (!empty($extra) && str_contains(strtolower($extra), 'unsigned')) {
            $info['unsigned'] = true;
            $extra = trim(str_ireplace('unsigned', '', $extra));
        }

        if (!empty($extra)) {
            if (empty($info['extra'])) {
                $info['extra'] = $extra;
            } else {
                $info['extra'] = $extra . ' ' . $info['extra'];
            }
        }

        if (in_array($dbType, self::BUILDERS, true)) {
            return $this->columnBuilderClass::$dbType()->load($info);
        }

        if (in_array($dbType, self::TYPES, true)) {
            return $this->fromType($dbType, $info);
        }

        return $this->fromDbType($dbType, $info);
    }

    public function fromPhpType(string $phpType, array $info = []): ColumnInterface
    {
        $type = $info['type'] ?? $this->getTypeFromPhp($phpType);

        $column = match ($phpType) {
            SchemaInterface::PHP_TYPE_INTEGER => new IntegerColumn($type, $phpType),
            SchemaInterface::PHP_TYPE_DOUBLE => new DoubleColumn($type, $phpType),
            SchemaInterface::PHP_TYPE_BOOLEAN => new BooleanColumn($type, $phpType),
            SchemaInterface::PHP_TYPE_RESOURCE => new BinaryColumn($type, $phpType),
            SchemaInterface::PHP_TYPE_ARRAY => new JsonColumn($type, $phpType),
            default => new StringColumn($type, $phpType),
        };

        $column->load($info);

        return $column;
    }

    public function fromType(string $type, array $info = []): ColumnInterface
    {
        $info['type'] = $type;
        $phpType = $info['php_type'] ?? $this->getPhpType($type);

        if (isset($this->fromType[$type])) {
            return (new $this->fromType[$type]($type, $phpType))->load($info);
        }

        $isUnsigned = !empty($info['unsigned']);

        if (
            PHP_INT_SIZE !== 8 && $isUnsigned && $type === SchemaInterface::TYPE_INTEGER
            || (PHP_INT_SIZE !== 8 || $isUnsigned) && $type === SchemaInterface::TYPE_BIGINT
        ) {
            return (new BigIntColumn($type, $phpType))->load($info);
        }

        return $this->fromPhpType($phpType, $info);
    }

    /**
     * Get the abstract database type from a database column type.
     *
     * @param string $dbType The database column type.
     *
     * @return string The abstract database type.
     */
    protected function getType(string $dbType): string
    {
        if (in_array($dbType, self::TYPES, true)) {
            return $dbType;
        }

        return SchemaInterface::TYPE_STRING;
    }

    /**
     * Get the PHP type from an abstract database type.
     *
     * @param string $type The abstract database type.
     *
     * @return string The PHP type name.
     */
    protected function getPhpType(string $type): string
    {
        return match ($type) {
            // abstract type => php type
            SchemaInterface::TYPE_BOOLEAN => SchemaInterface::PHP_TYPE_BOOLEAN,
            SchemaInterface::TYPE_BIT => SchemaInterface::PHP_TYPE_INTEGER,
            SchemaInterface::TYPE_TINYINT => SchemaInterface::PHP_TYPE_INTEGER,
            SchemaInterface::TYPE_SMALLINT => SchemaInterface::PHP_TYPE_INTEGER,
            SchemaInterface::TYPE_INTEGER => SchemaInterface::PHP_TYPE_INTEGER,
            SchemaInterface::TYPE_BIGINT => SchemaInterface::PHP_TYPE_INTEGER,
            SchemaInterface::TYPE_DECIMAL => SchemaInterface::PHP_TYPE_DOUBLE,
            SchemaInterface::TYPE_FLOAT => SchemaInterface::PHP_TYPE_DOUBLE,
            SchemaInterface::TYPE_DOUBLE => SchemaInterface::PHP_TYPE_DOUBLE,
            SchemaInterface::TYPE_BINARY => SchemaInterface::PHP_TYPE_RESOURCE,
            SchemaInterface::TYPE_JSON => SchemaInterface::PHP_TYPE_ARRAY,
            SchemaInterface::TYPE_ARRAY => SchemaInterface::PHP_TYPE_ARRAY,
            SchemaInterface::TYPE_COMPOSITE => SchemaInterface::PHP_TYPE_ARRAY,
            default => SchemaInterface::PHP_TYPE_STRING,
        };
    }

    protected function getTypeFromPhp(string $phpType): string
    {
        return match ($phpType) {
            // php type => abstract type
            SchemaInterface::PHP_TYPE_INTEGER => SchemaInterface::TYPE_INTEGER,
            SchemaInterface::PHP_TYPE_BOOLEAN => SchemaInterface::TYPE_BOOLEAN,
            SchemaInterface::PHP_TYPE_DOUBLE => SchemaInterface::TYPE_DOUBLE,
            SchemaInterface::PHP_TYPE_RESOURCE => SchemaInterface::TYPE_BINARY,
            SchemaInterface::PHP_TYPE_ARRAY => SchemaInterface::TYPE_JSON,
            default => SchemaInterface::TYPE_STRING,
        };
    }
}
