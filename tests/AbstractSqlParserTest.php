<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Syntax\AbstractSqlParser;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class AbstractSqlParserTest extends TestCase
{
    use TestTrait;

    /** @dataProvider \Yiisoft\Db\Tests\Provider\SqlParserProvider::getNextPlaceholder */
    public function testGetNextPlaceholder(string $sql, ?string $expectedPlaceholder, ?int $expectedPosition): void
    {
        $parser = $this->createSqlParser($sql);

        $this->assertSame($expectedPlaceholder, $parser->getNextPlaceholder($position));
        $this->assertSame($expectedPosition, $position);
    }

    /** @dataProvider \Yiisoft\Db\Tests\Provider\SqlParserProvider::getAllPlaceholders */
    public function testGetAllPlaceholders(string $sql, array $expectedPlaceholders, array $expectedPositions): void
    {
        $parser = $this->createSqlParser($sql);

        $placeholders = [];
        $positions = [];

        while (null !== $placeholder = $parser->getNextPlaceholder($position)) {
            $placeholders[] = $placeholder;
            $positions[] = $position;
        }

        $this->assertSame($expectedPlaceholders, $placeholders);
        $this->assertSame($expectedPositions, $positions);
    }

    abstract protected function createSqlParser(string $sql): AbstractSqlParser;
}
