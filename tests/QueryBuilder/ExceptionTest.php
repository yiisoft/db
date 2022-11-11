<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Tests\Support\Mock;
use Yiisoft\Db\Tests\Support\Stubs\ExpressionStub;

/**
 * @group db
 */
final class ExceptionTest extends TestCase
{
    private QueryBuilderInterface $queryBuilder;

    public function setUp(): void
    {
        parent::setUp();

        $this->queryBuilder = Mock::getConnection()->getQueryBuilder();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($this->queryBuilder);
    }

    public function testAddDefaultValue(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DDLQueryBuilder does not support adding default value constraints.'
        );

        $this->queryBuilder->addDefaultValue('name', 'table', 'column', 'value');
    }

    public function testBuilColumnStringException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Column name must be a string or an instance of ExpressionInterface.'
        );

        $this->queryBuilder->buildColumns([1]);
    }

    public function testBuildJoinException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'A join clause must be specified as an array of join type, join table, and optionally join condition.'
        );

        $params = [];
        $this->queryBuilder->buildJoin([1], $params);
    }

    public function testCheckIntegrityException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DDLQueryBuilder does not support enabling/disabling integrity check.'
        );

        $this->queryBuilder->checkIntegrity('schema', 'table', true);
    }

    public function testDropDefaultValueException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DDLQueryBuilder does not support dropping default value constraints.'
        );

        $this->queryBuilder->dropDefaultValue('name', 'table', 'column');
    }

    public function testGetExpressionBuilderException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expression of class Yiisoft\Db\Tests\Support\Stubs\ExpressionStub can not be built in Yiisoft\Db\Tests\Support\Stubs\DQLQueryBuilder'
        );

        $this->queryBuilder->getExpressionBuilder(new ExpressionStub());
    }

    public function testResetSequenceException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DMLQueryBuilder does not support resetting sequence.'
        );

        $this->queryBuilder->resetSequence('table', 'column');
    }

    public function testUpsertException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\DMLQueryBuilder does not support upsert.'
        );

        $params = [];
        $this->queryBuilder->upsert('table', ['column' => 'value'], [], $params);
    }
}
