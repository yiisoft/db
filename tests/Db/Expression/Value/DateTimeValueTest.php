<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Expression\Value\DateTimeType;
use Yiisoft\Db\Expression\Value\DateTimeValue;

use function PHPUnit\Framework\assertSame;

final class DateTimeValueTest extends TestCase
{
    public function testDefaults(): void
    {
        $expression = new DateTimeValue(new DateTimeImmutable());

        assertSame(DateTimeType::DateTimeTz, $expression->type);
    }
}
