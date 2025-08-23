<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\ColumnType;
use Yiisoft\Db\Expression\Value\DateTimeValue;

use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertSame;

final class DateTimeValueTest extends TestCase
{
    public function testDefaults(): void
    {
        $expression = new DateTimeValue(new DateTimeImmutable());

        assertSame(ColumnType::DATETIMETZ, $expression->type);
        assertNull($expression->info);
    }
}
