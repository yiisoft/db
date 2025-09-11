<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition;

enum LikeConjunction
{
    case And;
    case Or;
}
