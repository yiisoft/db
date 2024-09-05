<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constant;

/**
 * Defines the available column pseudo-types.
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
     * Define the column pseudo-type as big primary key.
     */
    public const BIGPK = 'bigpk';
    /**
     * Define the column pseudo-type as `unsigned` big primary key.
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
