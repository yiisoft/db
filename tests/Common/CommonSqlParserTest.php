<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Syntax\AbstractSqlParser;
use Yiisoft\Db\Tests\Provider\SqlParserProvider;

abstract class CommonSqlParserTest extends TestCase
{
    #[DataProviderExternal(SqlParserProvider::class, 'getNextPlaceholder')]
    public function testGetNextPlaceholder(string $sql, ?string $expectedPlaceholder, ?int $expectedPosition): void
    {
        $parser = $this->createSqlParser($sql);

        $this->assertSame($expectedPlaceholder, $parser->getNextPlaceholder($position));
        $this->assertSame($expectedPosition, $position);
    }

    #[DataProviderExternal(SqlParserProvider::class, 'getAllPlaceholders')]
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
