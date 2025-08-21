<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value;

enum DateTimeType
{
    case Date;
    case Time;
    case TimeTz;
    case DateTime;
    case DateTimeTz;
    case Timestamp;
}
