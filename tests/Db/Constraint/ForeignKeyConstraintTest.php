<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Constraint;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ForeignKeyConstraintTest extends TestCase
{
    public function testGetForeignSchemaName(): void
    {
        $foreignKeyConstraint = new ForeignKeyConstraint();

        $this->assertNull($foreignKeyConstraint->getForeignSchemaName());

        $foreignKeyConstraint = $foreignKeyConstraint->foreignSchemaName('foreignSchemaName');

        $this->assertSame('foreignSchemaName', $foreignKeyConstraint->getForeignSchemaName());
    }

    public function testGetForeignTableName(): void
    {
        $foreignKeyConstraint = new ForeignKeyConstraint();

        $this->assertNull($foreignKeyConstraint->getForeignTableName());

        $foreignKeyConstraint = $foreignKeyConstraint->foreignTableName('foreignTableName');

        $this->assertSame('foreignTableName', $foreignKeyConstraint->getForeignTableName());
    }

    public function testGetForeignColumnNames(): void
    {
        $foreignKeyConstraint = new ForeignKeyConstraint();

        $this->assertSame([], $foreignKeyConstraint->getForeignColumnNames());

        $foreignKeyConstraint = $foreignKeyConstraint->foreignColumnNames(['foreignColumnNames']);

        $this->assertSame(['foreignColumnNames'], $foreignKeyConstraint->getForeignColumnNames());
    }

    public function testGetOnUpdate(): void
    {
        $foreignKeyConstraint = new ForeignKeyConstraint();

        $this->assertNull($foreignKeyConstraint->getOnUpdate());

        $foreignKeyConstraint = $foreignKeyConstraint->onUpdate('onUpdate');

        $this->assertSame('onUpdate', $foreignKeyConstraint->getOnUpdate());
    }

    public function testGetOnDelete(): void
    {
        $foreignKeyConstraint = new ForeignKeyConstraint();

        $this->assertNull($foreignKeyConstraint->getOnDelete());

        $foreignKeyConstraint = $foreignKeyConstraint->onDelete('onDelete');

        $this->assertSame('onDelete', $foreignKeyConstraint->getOnDelete());
    }
}
