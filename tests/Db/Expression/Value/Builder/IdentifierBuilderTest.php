<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Expression\Value\Builder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Value\Builder\IdentifierBuilder;
use Yiisoft\Db\Expression\Value\Identifier;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
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
}
