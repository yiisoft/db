<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Constraint;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\CheckConstraint;

/**
 * @group db
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
