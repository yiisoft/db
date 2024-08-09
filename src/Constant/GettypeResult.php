<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constant;

/**
 * The following constants are returned by {@see gettype()} function.
 *
 * @link https://www.php.net/manual/en/function.gettype.php
 */
final class GettypeResult
{
    /**
     *  Define the php type as `array`.
     */
    public const ARRAY = 'array';
    /**
     *  Define the php type as `boolean`.
     */
    public const BOOLEAN = 'boolean';
    /**
     *  Define the php type as `double`.
     */
    public const DOUBLE = 'double';
    /**
     *  Define the php type as `integer`.
     */
    public const INTEGER = 'integer';
    /**
     *  Define the php type as `NULL`.
     */
    public const NULL = 'NULL';
    /**
     *  Define the php type as `object`.
     */
    public const OBJECT = 'object';
    /**
     *  Define the php type as `resource`.
     */
    public const RESOURCE = 'resource';
    /**
     *  Define the php type as `string`.
     */
    public const STRING = 'string';
    /**
     *  Define the php type as `unknown type`.
     */
    public const UNKNOWN = 'unknown type';
}
