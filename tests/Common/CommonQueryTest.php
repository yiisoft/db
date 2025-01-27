<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

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

    public function testCallbackAll(): void
    {
        $db = $this->getConnection(true);

        $query = (new Query($db))
            ->from('customer')
            ->callback(fn (array $row) => (object) $row);

        $this->assertEquals([
            (object) [
                'id' => '1',
                'email' => 'user1@example.com',
                'name' => 'user1',
                'address' => 'address1',
                'status' => '1',
                'profile_id' => '1',
            ],
            (object) [
                'id' => '2',
                'email' => 'user2@example.com',
                'name' => 'user2',
                'address' => 'address2',
                'status' => '1',
                'profile_id' => null,
            ],
            (object) [
                'id' => '3',
                'email' => 'user3@example.com',
                'name' => 'user3',
                'address' => 'address3',
                'status' => '2',
                'profile_id' => '2',
            ],
        ], $query->all());

        $db->close();
    }

    public function testCallbackOne(): void
    {
        $db = $this->getConnection(true);

        $query = (new Query($db))
            ->from('customer')
            ->where(['id' => 2])
            ->callback(fn (array $row) => (object) $row);

        $this->assertEquals((object) [
            'id' => '2',
            'email' => 'user2@example.com',
            'name' => 'user2',
            'address' => 'address2',
            'status' => '1',
            'profile_id' => null,
        ], $query->one());

        $db->close();
    }
}
