<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

enum DateTimeType
{
    case Timestamp;
    case DateTime;
    case DateTimeTz;
    case Time;
    case TimeTz;
    case Date;
    case Integer;
    case Float;
}
