<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Schema\SchemaInterface;

use function explode;
use function preg_match;
use function str_ireplace;
use function stripos;
use function strlen;
use function strtolower;
use function substr;
use function trim;

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
     */
    abstract protected function getType(string $dbType, array $info = []): string;

    public function fromDbType(string $dbType, array $info = []): ColumnSchemaInterface
    {
        $info['db_type'] = $dbType;
        $type = $info['type'] ?? $this->getType($dbType, $info);

        return $this->fromType($type, $info);
    }

    public function fromDefinition(string $definition, array $info = []): ColumnSchemaInterface
    {
        preg_match('/^(\w*)(?:\(([^)]+)\))?\s*/', $definition, $matches);

        $dbType = strtolower($matches[1]);

        if (isset($matches[2])) {
            $values = explode(',', $matches[2]);
            $info['size'] = (int) $values[0];
            $info['precision'] = (int) $values[0];

            if (isset($values[1])) {
                $info['scale'] = (int) $values[1];
            }
        }

        $extra = substr($definition, strlen($matches[0]));

        if (!empty($extra)) {
            if (stripos($extra, 'unsigned') !== false) {
                $info['unsigned'] = true;
                $extra = trim(str_ireplace('unsigned', '', $extra));
            }

            if (!empty($extra)) {
                if (empty($info['extra'])) {
                    $info['extra'] = $extra;
                } else {
                    /** @psalm-suppress MixedOperand */
                    $info['extra'] = $extra . ' ' . $info['extra'];
                }
            }
        }

        return $this->fromDbType($dbType, $info);
    }

    public function fromType(string $type, array $info = []): ColumnSchemaInterface
    {
        $column = match ($type) {
            SchemaInterface::TYPE_BOOLEAN => new BooleanColumnSchema($type),
            SchemaInterface::TYPE_BIT => new BitColumnSchema($type),
            SchemaInterface::TYPE_TINYINT => new IntegerColumnSchema($type),
            SchemaInterface::TYPE_SMALLINT => new IntegerColumnSchema($type),
            SchemaInterface::TYPE_INTEGER => PHP_INT_SIZE !== 8 && !empty($info['unsigned'])
                ? new BigIntColumnSchema($type)
                : new IntegerColumnSchema($type),
            SchemaInterface::TYPE_BIGINT => PHP_INT_SIZE !== 8 || !empty($info['unsigned'])
                ? new BigIntColumnSchema($type)
                : new IntegerColumnSchema($type),
            SchemaInterface::TYPE_DECIMAL => new DoubleColumnSchema($type),
            SchemaInterface::TYPE_FLOAT => new DoubleColumnSchema($type),
            SchemaInterface::TYPE_DOUBLE => new DoubleColumnSchema($type),
            SchemaInterface::TYPE_BINARY => new BinaryColumnSchema($type),
            SchemaInterface::TYPE_JSON => new JsonColumnSchema($type),
            default => new StringColumnSchema($type),
        };

        return $column->load($info);
    }
}
