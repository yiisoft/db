<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Schema\Column\AbstractColumn;

final class Column extends AbstractColumn
{
    protected const DEFAULT_TYPE = '';

    public function dbTypecast(mixed $value): mixed
    {
        return $value;
    }

    public function phpTypecast(mixed $value): mixed
    {
        return $value;
    }
}
