<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Constraint;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\IndexConstraint;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class IndexConstraintTest extends TestCase
{
    public function testIsUnique(): void
    {
        $indexConstraint = new IndexConstraint();

        $this->assertFalse($indexConstraint->isUnique());

        $indexConstraint = $indexConstraint->unique(true);

        $this->assertTrue($indexConstraint->isUnique());
    }

    public function testIsPrimary(): void
    {
        $indexConstraint = new IndexConstraint();

        $this->assertFalse($indexConstraint->isPrimary());

        $indexConstraint = $indexConstraint->primary(true);

        $this->assertTrue($indexConstraint->isPrimary());
    }
}
