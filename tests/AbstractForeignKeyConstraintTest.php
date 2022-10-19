<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\ForeignKeyConstraint;

abstract class AbstractForeignKeyConstraintTest extends TestCase
{
    public function testGetSchemaForeignKeys(): void
    {
        $db = $this->getConnection(false);

        $tableForeignKeys = $db->getSchema()->getSchemaForeignKeys();

        $this->assertIsArray($tableForeignKeys);

        foreach ($tableForeignKeys as $foreignKeys) {
            $this->assertIsArray($foreignKeys);
            $this->assertContainsOnlyInstancesOf(ForeignKeyConstraint::class, $foreignKeys);
        }
    }
}
