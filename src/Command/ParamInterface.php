<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

interface ParamInterface
{
    /**
     * @param mixed $value The value to bind to the parameter.
     * @param int $type SQL data type of the parameter. If null, the type is determined by the PHP type of the
     * value.
     */
    public function __construct(mixed $value, int $type);

    /**
     * @return mixed
     */
    public function getValue(): mixed;

    /**
     * @return int
     */
    public function getType(): int;
}
