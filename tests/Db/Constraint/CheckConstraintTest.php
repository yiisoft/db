<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Constraint;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\CheckConstraint;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class CheckConstraintTest extends TestCase
{
    public function testGetExpression(): void
    {
        $checkConstraint = new CheckConstraint();

        $this->assertSame('', $checkConstraint->getExpression());

        $checkConstraint = $checkConstraint->expression('expression');

        $this->assertSame('expression', $checkConstraint->getExpression());
    }
}
