<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support;

enum StringEnum: string
{
    case EMPTY = '';
    case ONE = 'one';
    case TWO = 'two';
    case THREE = 'three';
}
