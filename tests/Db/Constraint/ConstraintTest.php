<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Constraint;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\AbstractConstraint;
use Yiisoft\Db\Constraint\CheckConstraint;
use Yiisoft\Db\Constraint\DefaultValueConstraint;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;
use Yiisoft\Db\Constraint\IndexConstraint;

/**
 * @group db
 */
final class ConstraintTest extends TestCase
{
    public static function constrainClasses(): array
    {
        return [
            [CheckConstraint::class],
            [DefaultValueConstraint::class],
            [ForeignKeyConstraint::class],
            [IndexConstraint::class],
        ];
    }

    #[DataProvider('constrainClasses')]
    public function testGetColumnNames(string $className): void
    {
        /** @var AbstractConstraint $constraint */
        $constraint = new $className();

        $this->assertSame([], $constraint->getColumnNames());

        $constraint->columnNames(['columnNames']);

        $this->assertSame(['columnNames'], $constraint->getColumnNames());
    }

    #[DataProvider('constrainClasses')]
    public function testGetName(string $className): void
    {
        /** @var AbstractConstraint $constraint */
        $constraint = new $className();

        $this->assertSame('', $constraint->getName());

        $constraint->name('name');

        $this->assertSame('name', $constraint->getName());
    }
}
