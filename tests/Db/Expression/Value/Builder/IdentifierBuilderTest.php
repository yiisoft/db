<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value\Builder;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Value\Builder\IdentifierBuilder;
use Yiisoft\Db\Expression\Value\Identifier;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\Quoter;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Tests\Support\TestHelper;

/**
 * @group db
 */
final class IdentifierBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();
        $builder = new IdentifierBuilder($db->getQueryBuilder());

        $params = [];
        $result = $builder->build(new Identifier(['t', 'na]me']), $params);

        $this->assertSame('[t].[na]]me]', $result);
        $this->assertSame([], $params);
    }

    public function testBuildViaQueryBuilder(): void
    {
        $db = TestHelper::createSqliteMemoryConnection();

        $this->assertSame(
            '[s].[t].[LOWER(name)]',
            $db->getQueryBuilder()->buildExpression(new Identifier('s.t.LOWER(name)')),
        );
    }

    public function testBuildWithNotSupportedQuoter(): void
    {
        $quoter = $this->createStub(QuoterInterface::class);
        $queryBuilder = $this->createStub(QueryBuilderInterface::class);
        $queryBuilder->method('getQuoter')->willReturn($quoter);
        $builder = new IdentifierBuilder($queryBuilder);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Quoter "' . $quoter::class . '" must implement "Yiisoft\Db\Schema\IdentifierQuoterInterface" to build '
            . '"Yiisoft\Db\Expression\Value\Identifier" expressions.',
        );

        $builder->build(new Identifier('name'));
    }

    public static function dataBuildWithQuoteSqlSequences(): iterable
    {
        yield 'double quotes, square brackets' => ['"', '[[name]]', '"[[name]]"'];
        yield 'double quotes, curly brackets' => ['"', '{{t}}', '"{{t}}"'];
        yield 'double quotes, curly brackets with prefix' => ['"', '{{%t}}', '"{{%t}}"'];
        yield 'double quotes, square brackets inside' => ['"', 'x[[a b]]y', '"x[[a b]]y"'];
        yield 'square brackets, square brackets' => [['[', ']'], '[name]', '[[name]]]'];
        yield 'square brackets, square brackets inside' => [['[', ']'], '[w] - 1 - [z', '[[w]] - 1 - [z]'];
    }

    #[DataProvider('dataBuildWithQuoteSqlSequences')]
    public function testBuildWithQuoteSqlSequences(string|array $quoteCharacter, string $name, string $expected): void
    {
        $queryBuilder = $this->createStub(QueryBuilderInterface::class);
        $queryBuilder->method('getQuoter')->willReturn(new Quoter($quoteCharacter, $quoteCharacter));
        $builder = new IdentifierBuilder($queryBuilder);
        $identifier = new Identifier([$name]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Identifier ' . $expected . ' must not contain sequences processed by '
            . '"Yiisoft\Db\Schema\QuoterInterface::quoteSql()", such as "[[...]]" or "{{...}}".',
        );
        $builder->build($identifier);
    }

    public static function dataBuildWithBrackets(): iterable
    {
        yield 'double quotes, square brackets' => ['"', '[name]', '"[name]"'];
        yield 'double quotes, curly brackets' => ['"', '{name}', '"{name}"'];
        yield 'double quotes, empty square brackets' => ['"', '[[]]', '"[[]]"'];
        yield 'double quotes, square brackets with comma' => ['"', '[[a,b]]', '"[[a,b]]"'];
        yield 'square brackets, closing bracket' => [['[', ']'], 'na]me', '[na]]me]'];
        yield 'square brackets, opening bracket' => [['[', ']'], '[name', '[[name]'];
    }

    #[DataProvider('dataBuildWithBrackets')]
    public function testBuildWithBrackets(string|array $quoteCharacter, string $name, string $expected): void
    {
        $queryBuilder = $this->createStub(QueryBuilderInterface::class);
        $queryBuilder->method('getQuoter')->willReturn(new Quoter($quoteCharacter, $quoteCharacter));
        $builder = new IdentifierBuilder($queryBuilder);

        $this->assertSame($expected, $builder->build(new Identifier([$name])));
    }
}
