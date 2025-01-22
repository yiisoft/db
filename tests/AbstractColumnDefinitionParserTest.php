<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Syntax\ColumnDefinitionParser;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
abstract class AbstractColumnDefinitionParserTest extends TestCase
{
    use TestTrait;

    protected function createColumnDefinitionParser(): ColumnDefinitionParser
    {
        return new ColumnDefinitionParser();
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\ColumnDefinitionParserProvider::parse
     */
    public function testParse(string $definition, array $expected): void
    {
        $parser = $this->createColumnDefinitionParser();

        $this->assertSame($expected, $parser->parse($definition));
    }
}
