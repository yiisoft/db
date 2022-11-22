<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Constraint;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Db\Constraint\DefaultValueConstraint;

/**
 * @group db
 */
final class DefaultValueConstraintTest extends TestCase
{
    public function testGetValue(): void
    {
        $defaultValueConstraint = new DefaultValueConstraint();

        $this->assertNull($defaultValueConstraint->getValue());

        $defaultValueConstraint = $defaultValueConstraint->value('value');

        $this->assertSame('value', $defaultValueConstraint->getValue());

        $defaultValueConstraint = $defaultValueConstraint->value(new stdClass());

        $this->assertInstanceOf(stdClass::class, $defaultValueConstraint->getValue());
    }
}
