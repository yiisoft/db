<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

interface ParamInterface
{
    /**
     * @param int|string $name Parameter identifier. For a prepared statement using named placeholders, this will be a
     * parameter name of the form `:name`. For a prepared statement using question mark placeholders, this will be the
     * 1-indexed position of the parameter.
     * @param mixed $value The value to bind to the parameter.
     * @param int|null $type SQL data type of the parameter. If null, the type is determined by the PHP type of the
     * value.
     */
    public function __construct(int|string $name, mixed $value, ?int $type);

    /**
     * @return int|string
     */
    public function getName(): int|string;

    /**
     * @return mixed
     */
    public function getValue(): mixed;

    /**
     * @return int|null
     */
    public function getType(): ?int;
}
