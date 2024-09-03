<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

/**
 * The interface must be implemented by a column factory class. It should create a column schema for a database column
 * type and initialize column information.
 *
 * @psalm-import-type ColumnInfo from ColumnSchemaInterface
 */
interface ColumnFactoryInterface
{
    /**
     * Creates a column schema for a database column type and initializes column information.
     *
     * @param string $dbType The database column type.
     * @param array $info The column information.
     *
     * @psalm-param ColumnInfo $info The set of parameters may be different for a specific DBMS.
     */
    public function fromDbType(string $dbType, array $info = []): ColumnSchemaInterface;

    /**
     * Creates a column schema for a database column definition and initializes column information.
     *
     * @param string $definition The database column definition.
     * @param array $info The column information.
     *
     * @psalm-param ColumnInfo $info The set of parameters may be different for a specific DBMS.
     */
    public function fromDefinition(string $definition, array $info = []): ColumnSchemaInterface;

    /**
     * Creates a column schema for an abstract database type and initializes column information.
     *
     * @param string $type The abstract database type.
     * @param array $info The column information.
     *
     * @psalm-param ColumnInfo $info The set of parameters may be different for a specific DBMS.
     */
    public function fromType(string $type, array $info = []): ColumnSchemaInterface;
}
