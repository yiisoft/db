<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractQuoterTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::ensureColumnName()
     */
    public function testsEnsureColumnName(string $columnName, string $expected): void
    {
        $this->assertSame($expected, $this->getQuoter()->ensureColumnName($columnName));
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QuoterProvider::ensureNameQuoted()
     */
    public function testsEnsureNameQuoted(string $name, string $expected): void
    {
        $this->assertSame($expected, $this->getQuoter()->ensureNameQuoted($name));
    }
}
