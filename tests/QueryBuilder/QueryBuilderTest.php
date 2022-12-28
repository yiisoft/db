<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\QueryBuilder;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Tests\AbstractQueryBuilderTest;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class QueryBuilderTest extends AbstractQueryBuilderTest
{
    use TestTrait;

    public function testAddDefaultValue(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\DDLQueryBuilder::addDefaultValue is not supported by this DBMS.'
        );

        $qb->addDefaultValue('name', 'table', 'column', 'value');
    }

    /**
     * @dataProvider Yiisoft\Db\Tests\Provider\QueryBuilderProvider::batchInsert()
     */
    public function testBatchInsert(string $table, array $columns, array $rows, string $expected): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Schema::loadTableSchema is not supported by this DBMS.'
        );

        $qb->batchInsert('table', ['column1', 'column2'], [['value1', 'value2'], ['value3', 'value4']]);
    }

    public function testCheckIntegrity(): void
    {
        $db = $this->getConnection();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\DDLQueryBuilder::checkIntegrity is not supported by this DBMS.'
        );

        $qb = $db->getQueryBuilder();
        $qb->checkIntegrity('schema', 'table');
    }

    public function testDropDefaultValue(): void
    {
        $db = $this->getConnection(true);

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\DDLQueryBuilder::dropDefaultValue is not supported by this DBMS.'
        );

        $qb->dropDefaultValue('CN_pk', 'T_constraints_1');
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::insert()
     */
    public function testInsert(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Schema::loadTableSchema is not supported by this DBMS.'
        );

        $qb->insert($table, $columns, $params);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::insertEx()
     */
    public function testInsertEx(
        string $table,
        array|QueryInterface $columns,
        array $params,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\DMLQueryBuilder::insertEx() is not supported by this DBMS.'
        );

        $qb->insertEx($table, $columns, $params);
    }

    public function testResetSequence(): void
    {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\DMLQueryBuilder::resetSequence() is not supported by this DBMS.'
        );

        $qb->resetSequence('T_constraints_1', 'id');
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::update()
     */
    public function testUpdate(
        string $table,
        array $columns,
        array|string $condition,
        string $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $qb = $db->getQueryBuilder();
        $actualParams = [];

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Schema::loadTableSchema is not supported by this DBMS.'
        );

        $qb->update($table, $columns, $condition, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::upsert()
     *
     * @throws Exception
     * @throws JsonException
     * @throws NotSupportedException
     */
    public function testUpsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns,
        string|array $expectedSQL,
        array $expectedParams
    ): void {
        $db = $this->getConnection();

        $actualParams = [];

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\QueryBuilder\DMLQueryBuilder::upsert is not supported by this DBMS.'
        );

        $db->getQueryBuilder()->upsert($table, $insertColumns, $updateColumns, $actualParams);
    }

    /**
     * @dataProvider \Yiisoft\Db\Tests\Provider\QueryBuilderProvider::upsert()
     */
    public function testUpsertExecute(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns
    ): void {
        $this->markTestSkipped('Execute check needed only on real db');
    }
}
