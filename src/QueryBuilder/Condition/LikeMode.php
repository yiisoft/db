<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

/**
 * Like condition modes.
 */
enum LikeMode
{
    case Contains;
    case StartsWith;
    case EndsWith;
}
