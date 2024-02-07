<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

/**
 * @psalm-type ColumnInfo = array{
 *     allow_null?: bool|string|null,
 *     auto_increment?: bool|string,
 *     comment?: string|null,
 *     computed?: bool|string,
 *     db_type?: string|null,
 *     default_value?: mixed,
 *     default_value_raw?: string|null,
 *     extra?: string|null,
 *     primary_key?: bool|string,
 *     name?: string|null,
 *     php_type?: string|null,
 *     scale?: int|string|null,
 *     schema?: string|null,
 *     size?: int|string|null,
 *     table?: string|null,
 *     type?: string|null,
 *     unsigned?: bool|string,
 *     values?: string[]|null,
 * }
 */
interface ColumnFactoryInterface
{
    /**
     * Creates a column schema for the database column type and initializes column information.
     *
     * @param string $dbType The database column type.
     * @param array $info The column information.
     *
     * @psalm-param ColumnInfo $info The set of parameters may be different for a specific DBMS.
     */
    public function fromDbType(string $dbType, array $info = []): ColumnInterface;

    public function fromDefinition(string $definition, array $info = []): ColumnInterface;

    public function fromPhpType(string $phpType, array $info = []): ColumnInterface;

    /**
     * Creates a column schema for the abstract database type and initializes column information.
     *
     * @param string $type The abstract database type.
     * @param array $info The column information.
     *
     * @psalm-param ColumnInfo $info The set of parameters may be different for a specific DBMS.
     */
    public function fromType(string $type, array $info = []): ColumnInterface;

    /**
     * Get the column builder class name.
     *
     * @return string The column builder class name.
     */
    public function getBuilderClass(): string;
}
