<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Syntax\ColumnDefinitionParser;
use Yiisoft\Db\Tests\Provider\ColumnDefinitionParserProvider;

abstract class CommonColumnDefinitionParserTest extends TestCase
{
    #[DataProviderExternal(ColumnDefinitionParserProvider::class, 'parse')]
    public function testParse(string $definition, array $expected): void
    {
        $parser = $this->createColumnDefinitionParser();

        $this->assertSame($expected, $parser->parse($definition));
    }

    abstract protected function createColumnDefinitionParser(): ColumnDefinitionParser;
}
