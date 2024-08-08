<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Schema\Column\AbstractColumnSchema;

final class ColumnSchema extends AbstractColumnSchema
{
    public function __construct(
        private string $type = '',
    ) {
        parent::__construct($type);
    }

    public function dbTypecast(mixed $value): mixed
    {
        return $value;
    }

    public function phpTypecast(mixed $value): mixed
    {
        return $value;
    }
}
