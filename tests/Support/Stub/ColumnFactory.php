<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\Schema\Column\AbstractColumnFactory;

class ColumnFactory extends AbstractColumnFactory
{
    protected function getType(string $dbType, array $info = []): string
    {
        return $dbType;
    }
}
