<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\CheckConstraint;

abstract class AbstractCheckConstraintTest extends TestCase
{
    public function testGetSchemaCheckConstraint(): void
    {
        $db = $this->getConnection();

        $tableChecks = $db->getSchema()->getSchemaChecks();

        $this->assertIsArray($tableChecks);

        foreach ($tableChecks as $checks) {
            $this->assertIsArray($checks);
            $this->assertContainsOnlyInstancesOf(CheckConstraint::class, $checks);
        }
    }
}
