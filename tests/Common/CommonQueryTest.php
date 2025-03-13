<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractQueryTest;

abstract class CommonQueryTest extends AbstractQueryTest
{
    public function testColumnIndexByWithClosure()
    {
        $db = $this->getConnection(true);

        $result = (new Query($db))
            ->select(['id', 'name'])
            ->from('customer')
            ->indexBy(fn ($row) => $row['id'] * 2)
            ->column();

        $this->assertEquals([2 => '1', 4 => '2', 6 => '3'], $result);

        $db->close();
    }

    public function testWithQuery()
    {
        $db = $this->getConnection(true);

        $with = (new Query($db))
            ->distinct()
            ->select(['status'])
            ->from('customer');

        $query = (new Query($db))
            ->withQuery($with, 'statuses')
            ->from('statuses');

        $this->assertEquals(2, $query->count());

        $db->close();
    }

    public function testWithQueryRecursive()
    {
        $db = $this->getConnection();
        $quoter = $db->getQuoter();
        $isOracle = $db->getDriverName() === 'oci';

        /** Sum 1 to 10 equals 55 */
        $quotedName = $quoter->quoteColumnName('n');
        $union = (new Query($db))
            ->select(new Expression($quotedName . ' + 1'))
            ->from('t')
            ->where(['<', 'n', 10]);

        $with = (new Query($db))
            ->select(new Expression('1'))
            ->from($isOracle ? new Expression('DUAL') : [])
            ->union($union, true);

        $sum = (new Query($db))
            ->withQuery($with, 't(n)', true)
            ->from('t')
            ->sum($quotedName);

        $this->assertEquals(55, $sum);

        $db->close();
    }

    public function testSelectWithoutFrom()
    {
        $db = $this->getConnection();

        $query = (new Query($db))->select(new Expression('1'));

        $this->assertEquals(1, $query->scalar());

        $db->close();
    }

    public static function dataLike(): iterable
    {
        yield 'sameCase-defaultCaseSensitive' => ['user1', ['like', 'name', 'user1']];
        yield 'sameCase-caseSensitive' => ['user1', ['like', 'name', 'user1', true]];
        yield 'sameCase-caseInsensitive' => ['user1', ['like', 'name', 'user1', false]];
        yield 'otherCase-caseSensitive' => [false, ['like', 'name', 'USER1', true]];
        yield 'otherCase-caseInsensitive' => ['user1', ['like', 'name', 'USER1', false]];
    }

    #[DataProvider('dataLike')]
    public function testLike(mixed $expected, array $where): void
    {
        $db = $this->getConnection(true);

        $query = (new Query($db))->select('name')->from('customer')->where($where);

        if ($expected instanceof Exception) {
            $this->expectException($expected::class);
            $this->expectExceptionMessage($expected->getMessage());
            $query->scalar();
            return;
        }

        $this->assertSame($expected, $query->scalar());
    }
}
