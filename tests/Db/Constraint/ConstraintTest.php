<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Constraint;

use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Db\Constraint\Constraint;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConstraintTest extends TestCase
{
    public function testGetColumnNames(): void
    {
        $constraint = new Constraint();

        $this->assertNull($constraint->getColumnNames());

        $constraint->columnNames('columnNames');

        $this->assertSame('columnNames', $constraint->getColumnNames());

        $constraint->columnNames(['columnNames']);

        $this->assertSame(['columnNames'], $constraint->getColumnNames());
    }

    public function testGetName(): void
    {
        $constraint = new Constraint();

        $this->assertNull($constraint->getName());

        $constraint->name('name');

        $this->assertSame('name', $constraint->getName());

        $constraint->name(new stdClass());

        $this->assertInstanceOf(stdClass::class, $constraint->getName());
    }
}
