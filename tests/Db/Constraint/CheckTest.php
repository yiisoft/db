<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Constraint;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\Check;

/**
 * @group db
 */
final class CheckTest extends TestCase
{
    public function testDefaults(): void
    {
        $check = new Check();

        $this->assertSame('', $check->name);
        $this->assertSame([], $check->columnNames);
        $this->assertSame('', $check->expression);
    }

    public function testValues(): void
    {
        $check = new Check(
            'not_empty',
            ['column_name'],
            "column_name != ''",
        );

        $this->assertSame('not_empty', $check->name);
        $this->assertSame(['column_name'], $check->columnNames);
        $this->assertSame("column_name != ''", $check->expression);
    }
}
