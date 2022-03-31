<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestSupport;

use function strtolower;

final class AnyCaseValue extends CompareValue
{
    public $value;

    /**
     * @psalm-param string|string[] $value
     */
    public function __construct(string|array $value)
    {
        foreach ((array) $value as $v) {
            $this->value[] = strtolower($v);
        }
    }
}
