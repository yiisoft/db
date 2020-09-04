<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

use function array_map;
use function is_array;
use function strtolower;

final class AnyCaseValue extends CompareValue
{
    public $value;

    /**
     * Constructor.
     *
     * @param string|string[] $value
     */
    public function __construct($value)
    {
        if (is_array($value)) {
            $this->value = array_map('strtolower', $value);
        } else {
            $this->value = strtolower($value);
        }
    }
}
