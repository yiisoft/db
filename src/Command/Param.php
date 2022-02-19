<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

final class Param implements ParamInterface
{
    public function __construct(private string|int $name, private mixed $value, private ?int $type)
    {
    }

    public function getName(): string|int
    {
        return $this->name;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getType(): ?int
    {
        return $this->type;
    }
}
