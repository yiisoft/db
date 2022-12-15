<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

use function strtolower;

final class AnyCaseValue extends CompareValue
{
    public string|array $value = '';

    /**
     * @psalm-param string|string[] $value
     */
    public function __construct(string|array $value)
    {
        if (is_array($value)) {
            foreach ($value as $v) {
                $this->value = strtolower($v);
            }
        } else {
            $this->value = strtolower($value);
        }
    }
}
