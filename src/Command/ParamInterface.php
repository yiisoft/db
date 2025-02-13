<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use Yiisoft\Db\Constant\DataType;

/**
 * This interface represents a parameter to bind to an SQL statement.
 */
interface ParamInterface
{
    /**
     * @param mixed $value The value to bind to the parameter.
     * @param int $type The SQL data type of the parameter.
     * If `null`, the type is determined by the PHP type of the value.
     *
     * @psalm-param DataType::* $type
     */
    public function __construct(mixed $value, int $type);

    /**
     * @return int The SQL data type of the parameter.
     *
     * @psalm-return DataType::*
     */
    public function getType(): int;

    /**
     * @return mixed The value to bind to the parameter.
     */
    public function getValue(): mixed;
}
