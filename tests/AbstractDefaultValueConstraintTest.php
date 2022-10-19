<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\DefaultValueConstraint;

abstract class AbstractDefaultValueConstraintTest extends TestCase
{
    public function testGetSchemaDefaultValues(): void
    {
        $db = $this->getConnection();

        $tableDefaultValues = $db->getSchema()->getSchemaDefaultValues();

        $this->assertIsArray($tableDefaultValues);

        foreach ($tableDefaultValues as $defaultValues) {
            $this->assertIsArray($defaultValues);
            $this->assertContainsOnlyInstancesOf(DefaultValueConstraint::class, $defaultValues);
        }
    }
}
