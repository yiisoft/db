<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

interface ParamInterface
{
    public function __construct(string $name, mixed $value, ?int $type);

    public function getName(): string;

    public function getValue(): mixed;

    public function getType(): ?int;
}
