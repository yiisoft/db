<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Constraint\Constraint;

abstract class AbstractConstraintTest extends TestCase
{
    public function testGetSchemaPrimaryKeys(): void
    {
        $db = $this->getConnection();

        $tablePks = $db->getSchema()->getSchemaPrimaryKeys();

        $this->assertIsArray($tablePks);
        $this->assertContainsOnlyInstancesOf(Constraint::class, $tablePks);
    }

    public function testGetSchemaUniques(): void
    {
        $db = $this->getConnection();

        $tableUniques = $db->getSchema()->getSchemaUniques();

        $this->assertIsArray($tableUniques);

        foreach ($tableUniques as $uniques) {
            $this->assertIsArray($uniques);
            $this->assertContainsOnlyInstancesOf(Constraint::class, $uniques);
        }
    }
}
