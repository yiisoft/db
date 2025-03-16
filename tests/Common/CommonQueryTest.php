<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

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

    public function testLikeDefaultCaseSensitive(): void
    {
        $db = $this->getConnection(true);

        $result = (new Query($db))
            ->select('name')
            ->from('customer')
            ->where(['like', 'name', 'user1'])
            ->scalar();


        $this->assertSame('user1', $result);
    }

    public static function dataLikeCaseSensitive(): iterable
    {
        yield 'sameCase' => ['user1', 'user1'];
        yield 'otherCase' => [false, 'USER1'];
    }

    #[DataProvider('dataLikeCaseSensitive')]
    public function testLikeCaseSensitive(mixed $expected, string $value): void
    {
        $db = $this->getConnection(true);

        $result = (new Query($db))
            ->select('name')
            ->from('customer')
            ->where(['like', 'name', $value, 'caseSensitive' => true])
            ->scalar();

        $this->assertSame($expected, $result);
    }

    public static function dataLikeCaseInsensitive(): iterable
    {
        yield 'sameCase' => ['user1', 'user1'];
        yield 'otherCase' => ['user1', 'USER1'];
    }

    #[DataProvider('dataLikeCaseInsensitive')]
    public function testLikeCaseInsensitive(mixed $expected, string $value): void
    {
        $db = $this->getConnection(true);

        $result = (new Query($db))
            ->select('name')
            ->from('customer')
            ->where(['like', 'name', $value, 'caseSensitive' => false])
            ->scalar();

        $this->assertSame($expected, $result);
    }
}
