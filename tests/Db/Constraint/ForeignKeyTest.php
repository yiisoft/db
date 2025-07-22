<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Constraint;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constant\ReferentialAction;
use Yiisoft\Db\Constraint\ForeignKey;

/**
 * @group db
 */
final class ForeignKeyTest extends TestCase
{
    public function testDefaults(): void
    {
        $foreignKey = new ForeignKey();

        $this->assertSame('', $foreignKey->name);
        $this->assertSame([], $foreignKey->columnNames);
        $this->assertSame('', $foreignKey->foreignSchemaName);
        $this->assertSame('', $foreignKey->foreignTableName);
        $this->assertSame([], $foreignKey->foreignColumnNames);
        $this->assertNull($foreignKey->onDelete);
        $this->assertNull($foreignKey->onUpdate);
    }

    public function testValues(): void
    {
        $foreignKeyConstraint = new ForeignKey(
            'fk_name',
            ['column_name'],
            'foreign_schema',
            'foreign_table',
            ['foreign_column'],
            ReferentialAction::SET_NULL,
            ReferentialAction::CASCADE,
        );

        $this->assertSame('fk_name', $foreignKeyConstraint->name);
        $this->assertSame(['column_name'], $foreignKeyConstraint->columnNames);
        $this->assertSame('foreign_schema', $foreignKeyConstraint->foreignSchemaName);
        $this->assertSame('foreign_table', $foreignKeyConstraint->foreignTableName);
        $this->assertSame(['foreign_column'], $foreignKeyConstraint->foreignColumnNames);
        $this->assertSame(ReferentialAction::SET_NULL, $foreignKeyConstraint->onDelete);
        $this->assertSame(ReferentialAction::CASCADE, $foreignKeyConstraint->onUpdate);
    }
}
