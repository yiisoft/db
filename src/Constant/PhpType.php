<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constant;

use Yiisoft\Db\Schema\Column\ColumnSchemaInterface;

/**
 * Defines the available PHP types.
 * Used to generate properties of a related model class.
 *
 * @see ColumnSchemaInterface::getPhpType()
 * @see https://www.php.net/manual/en/language.types.type-system.php
 */
class PhpType
{
    /**
     * Define the php type as `array`.
     */
    public const ARRAY = 'array';
    /**
     * Define the php type as `bool`.
     */
    public const BOOL = 'bool';
    /**
     * Define the php type as `float`.
     */
    public const FLOAT = 'float';
    /**
     * Define the php type as `int`.
     */
    public const INT = 'int';
    /**
     * Define the php type as `mixed`.
     */
    public const MIXED = 'mixed';
    /**
     * Define the php type as `null`.
     */
    public const NULL = 'null';
    /**
     * Define the php type as `object`.
     */
    public const OBJECT = 'object';
    /**
     * Define the php type as `string`.
     */
    public const STRING = 'string';
}
