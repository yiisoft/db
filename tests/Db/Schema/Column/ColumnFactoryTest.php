<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Schema\Column;

use Yiisoft\Db\Tests\AbstractColumnFactoryTest;
use Yiisoft\Db\Tests\Support\Stub\ColumnFactory;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class ColumnFactoryTest extends AbstractColumnFactoryTest
{
    use TestTrait;

    protected function getColumnFactoryClass(): string
    {
        return ColumnFactory::class;
    }
}
