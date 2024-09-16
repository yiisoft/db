<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Syntax;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Syntax\ColumnDefinitionParser;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class ColumnDefinitionParserTest extends TestCase
{
    use TestTrait;

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\ColumnDefinitionParserProvider::parse
     */
    public function testParse(string $definition, array $expected): void
    {
        $parser = new ColumnDefinitionParser();

        $this->assertSame($expected, $parser->parse($definition));
    }
}
