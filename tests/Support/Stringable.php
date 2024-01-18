<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

class Stringable implements \Stringable
{
    public function __construct(private string $value)
    {
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
