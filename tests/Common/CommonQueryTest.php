<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Yiisoft\Db\Driver\PDO\ConnectionPDOInterface;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Schema\Schema;
use Yiisoft\Db\Tests\AbstractQueryTest;

/**
 * @group mssql
 * @group mysql
 * @group pgsql
 * @group oracle
 * @group sqlite
 */
abstract class CommonQueryTest extends AbstractQueryTest
{
    public function testColumn(): void
    {
        $db = $this->getConnectionWithData();

        $result = (new Query($db))->select('name')->from('customer')->orderBy(['id' => SORT_DESC])->column();

        $this->assertSame(['user3', 'user2', 'user1'], $result);

        /**
         * {@see https://github.com/yiisoft/yii2/issues/7515}
         */
        $result = (new Query($db))
            ->from('customer')
            ->select('name')
            ->orderBy(['id' => SORT_DESC])
            ->indexBy('id')
            ->column();

        $this->assertSame([3 => 'user3', 2 => 'user2', 1 => 'user1'], $result);

        /**
         * {@see https://github.com/yiisoft/yii2/issues/12649}
         */
        $result = (new Query($db))
            ->from('customer')
            ->select(['name', 'id'])
            ->orderBy(['id' => SORT_DESC])
            ->indexBy(fn ($row) => $row['id'] * 2)
            ->column();

        $this->assertSame([6 => 'user3', 4 => 'user2', 2 => 'user1'], $result);

        $result = (new Query($db))
            ->from('customer')
            ->select(['name'])
            ->indexBy('name')
            ->orderBy(['id' => SORT_DESC])
            ->column();

        $this->assertSame(['user3' => 'user3', 'user2' => 'user2', 'user1' => 'user1'], $result);

        $result = (new Query($db))
            ->from('customer')
            ->select(['name'])
            ->where(['id' => 10])
            ->orderBy(['id' => SORT_DESC])
            ->column();

        $this->assertSame([], $result);
    }

    public function testCount(): void
    {
        $db = $this->getConnectionWithData();

        $count = (new Query($db))->from('customer')->count('*');

        $this->assertSame(3, $count);

        $count = (new Query($db))->from('customer')->where(['status' => 2])->count('*');

        $this->assertSame(1, $count);

        $count = (new Query($db))
            ->select('[[status]], COUNT([[id]]) cnt')
            ->from('customer')
            ->groupBy('status')
            ->count('*');

        $this->assertSame(2, $count);

        /* testing that orderBy() should be ignored here as it does not affect the count anyway. */
        $count = (new Query($db))->from('customer')->orderBy('status')->count('*');

        $this->assertSame(3, $count);

        $count = (new Query($db))->from('customer')->orderBy('id')->limit(1)->count('*');

        $this->assertSame(3, $count);
    }

    public function testExists(): void
    {
        $db = $this->getConnectionWithData();

        $result = (new Query($db))->from('customer')->where(['status' => 2])->exists();

        $this->assertTrue($result);

        $result = (new Query($db))->from('customer')->where(['status' => 3])->exists();

        $this->assertFalse($result);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/15355}
     */
    public function testExpressionInFrom(): void
    {
        $db = $this->getConnectionWithData();

        $query = (new Query($db))
            ->from(
                new Expression(
                    '(SELECT [[id]], [[name]], [[email]], [[address]], [[status]] FROM {{customer}}) c'
                )
            )
            ->where(['status' => 2]);

        $result = $query->one();

        $this->assertSame('user3', $result['name']);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/13745}
     */
    public function testMultipleLikeConditions(): void
    {
        $db = $this->getConnection();

        $tableName = 'like_test';
        $columnName = 'col';

        if ($db->getSchema()->getTableSchema($tableName) !== null) {
            $db->createCommand()->dropTable($tableName)->execute();
        }

        $db->createCommand()->createTable(
            $tableName,
            [$columnName => $db->getSchema()->createColumnSchemaBuilder(Schema::TYPE_STRING, 64)],
        )->execute();

        $db->createCommand()->batchInsert(
            $tableName,
            ['col'],
            [
                ['test0'],
                ['test\1'],
                ['test\2'],
                ['foo%'],
                ['%bar'],
                ['%baz%'],
            ],
        )->execute();

        /* Basic tests */
        $this->assertSame(1, $this->countLikeQuery($db, $tableName, $columnName, ['test0']));
        $this->assertSame(2, $this->countLikeQuery($db, $tableName, $columnName, ['test\\']));
        $this->assertSame(0, $this->countLikeQuery($db, $tableName, $columnName, ['test%']));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, ['%']));

        /* Multiple condition tests */
        $this->assertSame(2, $this->countLikeQuery($db, $tableName, $columnName, ['test0', 'test\1']));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, ['test0', 'test\1', 'test\2']));
        $this->assertSame(3, $this->countLikeQuery($db, $tableName, $columnName, ['foo', '%ba']));
    }

    public function testLimitOffsetWithExpression(): void
    {
        $db = $this->getConnectionWithData();

        $query = (new Query($db))->from('customer')->select('id')->orderBy('id');
        $query->limit(new Expression('1 + 1'))->offset(new Expression('1 + 0'));
        $result = $query->column();

        $this->assertCount(2, $result);

        $driverName = $db->getName();

        if ($driverName !== 'sqlsrv' && $driverName !== 'oci') {
            $this->assertContains(2, $result);
            $this->assertContains(3, $result);
        } else {
            $this->assertContains('2', $result);
            $this->assertContains('3', $result);
        }

        $this->assertNotContains(1, $result);
    }

    public function testOne(): void
    {
        $db = $this->getConnectionWithData();

        $result = (new Query($db))->from('customer')->where(['status' => 2])->one();

        $this->assertEquals('user3', $result['name']);

        $result = (new Query($db))->from('customer')->where(['status' => 3])->one();

        $this->assertNull($result);
    }

    private function countLikeQuery(
        ConnectionPDOInterface $db,
        string $tableName,
        string $columnName,
        array $condition,
        string $operator = 'or'
    ): int {
        $whereCondition = [$operator];

        foreach ($condition as $value) {
            $whereCondition[] = ['like', $columnName, $value];
        }

        $result = (new Query($db))->from($tableName)->where($whereCondition)->count('*');

        if (is_numeric($result)) {
            return (int) $result;
        }

        return 0;
    }
}
