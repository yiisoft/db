<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\IndexConstraint;

abstract class AbstractIndexConstraintTest extends TestCase
{
    public function testGetSchemaIndexes(): void
    {
        $db = $this->getConnection();

        $tableIndexes = $db->getSchema()->getSchemaIndexes();

        $this->assertIsArray($tableIndexes);

        foreach ($tableIndexes as $indexes) {
            $this->assertIsArray($indexes);
            $this->assertContainsOnlyInstancesOf(IndexConstraint::class, $indexes);
        }
    }
}
