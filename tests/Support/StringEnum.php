<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

enum StringEnum: string
{
    case ONE = 'one';
    case TWO = 'two';
    case THREE = 'three';
}
