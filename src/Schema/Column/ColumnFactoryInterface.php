<?php

declare(strict_types=1);

namespace Yiisoft\Db\Schema\Column;

use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Constant\PseudoType;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;

/**
 * The interface must be implemented by a column factory class. It should create an instance of {@see ColumnInterface}
 * for a database column type and initialize column information.
 *
 * @psalm-type ColumnInfo = array{
 *     autoIncrement?: bool,
 *     check?: string|null,
 *     column?: ColumnInterface|null,
 *     columns?: array<string, ColumnInterface>,
 *     comment?: string|null,
 *     computed?: bool,
 *     dbType?: string|null,
 *     defaultValue?: mixed,
 *     defaultValueRaw?: string|null,
 *     dimension?: positive-int,
 *     enumValues?: array|null,
 *     extra?: string|null,
 *     primaryKey?: bool,
 *     name?: string|null,
 *     notNull?: bool,
 *     reference?: ForeignKeyConstraint|null,
 *     scale?: int|null,
 *     schema?: string|null,
 *     size?: int|null,
 *     table?: string|null,
 *     type?: ColumnType::*,
 *     unique?: bool,
 *     unsigned?: bool,
 * }
 */
interface ColumnFactoryInterface
{
    /**
     * Creates an instance of {@see ColumnInterface} for a database column type and initializes column information.
     *
     * @param string $dbType The database column type.
     * @param array $info The column information. The set of parameters may be different for a specific DBMS.
     *
     * @psalm-param ColumnInfo $info
     */
    public function fromDbType(string $dbType, array $info = []): ColumnInterface;

    /**
     * Creates an instance of {@see ColumnInterface} for a database column definition and initializes column information.
     * The definition string can contain a native database column type or {@see ColumnType abstract} type
     * or {@see PseudoType pseudo} type, its size, default value, etc.
     *
     * For example, `varchar(255) NOT NULL` is `varchar` database type with `255` size and a `NOT NULL` constraint.
     *
     * @param string $definition The database column definition.
     * @param array $info The column information. The set of parameters may be different for a specific DBMS.
     *
     * @psalm-param ColumnInfo $info
     */
    public function fromDefinition(string $definition, array $info = []): ColumnInterface;

    /**
     * Creates an instance of {@see ColumnInterface} for a pseudo-type and initializes column information.
     *
     * @param string $pseudoType The pseudo-type.
     * @param array $info The column information. The set of parameters may be different for a specific DBMS.
     *
     * @psalm-param PseudoType::* $pseudoType
     * @psalm-param ColumnInfo $info
     */
    public function fromPseudoType(string $pseudoType, array $info = []): ColumnInterface;

    /**
     * Creates an instance of {@see ColumnInterface} for an abstract database type and initializes column information.
     *
     * @param string $type The abstract database type.
     * @param array $info The column information. The set of parameters may be different for a specific DBMS.
     *
     * @psalm-param ColumnType::* $type
     * @psalm-param ColumnInfo $info
     */
    public function fromType(string $type, array $info = []): ColumnInterface;
}
