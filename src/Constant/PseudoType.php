<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constant;

/**
 * Defines the available column pseudo-types.
 * Used to define column primary key types when creating or updating a table schema.
 */
final class PseudoType
{
    /**
     * Define the column pseudo-type a primary key.
     */
    public const PK = 'pk';
    /**
     * Define the column pseudo-type as an `unsigned` primary key.
     */
    public const UPK = 'upk';
    /**
     * Define the column pseudo-type as a big primary key.
     */
    public const BIGPK = 'bigpk';
    /**
     * Define the column pseudo-type as an `unsigned` big primary key.
     */
    public const UBIGPK = 'ubigpk';
    /**
     * Define the column pseudo-type as an `uuid` primary key.
     */
    public const UUID_PK = 'uuid_pk';
    /**
     * Define the column pseudo-type as an `uuid` primary key with a sequence.
     */
    public const UUID_PK_SEQ = 'uuid_pk_seq';
}
