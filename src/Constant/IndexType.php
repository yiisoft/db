<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constant;

/**
 * Defines the available index types. It is used in {@see DDLQueryBuilderInterface::createIndex()}.
 */
final class IndexType
{
    /**
     * Define the type of the index as `UNIQUE`.
     *
     * Supported by `MySQL`, `MariaDB`, `MSSQL`, `Oracle`, `PostgreSQL`, `SQLite`.
     */
    public const UNIQUE = 'UNIQUE';
    /**
     * Define the type of the index as `BTREE`.
     *
     * Supported by `MySQL`, `PostgreSQL`.
     */
    public const BTREE = 'BTREE';
    /**
     * Define the type of the index as `HASH`.
     *
     * Supported by `MySQL`, `PostgreSQL`.
     */
    public const HASH = 'HASH';
    /**
     * Define the type of the index as `FULLTEXT`.
     *
     * Supported by `MySQL`.
     */
    public const FULLTEXT = 'FULLTEXT';
    /**
     * Define the type of the index as `SPATIAL`.
     *
     * Supported by `MySQL`.
     */
    public const SPATIAL = 'SPATIAL';
    /**
     * Define the type of the index as `GIST`.
     *
     * Supported by `PostgreSQL`.
     */
    public const GIST = 'GIST';
    /**
     * Define the type of the index as `GIN`.
     *
     * Supported by `PostgreSQL`.
     */
    public const GIN = 'GIN';
    /**
     * Define the type of the index as `BRIN`.
     *
     * Supported by `PostgreSQL`.
     */
    public const BRIN = 'BRIN';
    /**
     * Define the type of the index as `CLUSTERED`.
     *
     * Supported by `MSSQL`.
     */
    public const CLUSTERED = 'CLUSTERED';
    /**
     * Define the type of the index as `NONCLUSTERED`.
     *
     * Supported by `MSSQL`.
     */
    public const NONCLUSTERED = 'NONCLUSTERED';
    /**
     * Define the type of the index as `BITMAP`.
     *
     * Supported by `Oracle`.
     */
    public const BITMAP = 'BITMAP';
}
