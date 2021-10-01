<?php


declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\DataReader\Filter\All;
use Yiisoft\Db\DataReader\Filter\Equals;


class DataReaderTest extends TestCase
{
    public function testWithNull()
    {
        $equalsNull = (new Equals('test', null));
        $equalsNotNull = (new Equals('test', 1));
        $all = new All($equalsNull, $equalsNotNull);

        $this->assertEquals(['IS', 'test', null], $equalsNull->toArray());
        $this->assertEquals(['=', 'test', 1], $equalsNotNull->toArray());
        $this->assertEquals(['and', ['IS', 'test', null], ['=', 'test', 1]], $all->toArray());
    }

    public function testIgnoreNull()
    {
        $equalsNull = (new Equals('test', null))->withIgnoreNull(true);
        $equalsNotNull = (new Equals('test', 1))->withIgnoreNull(true);
        $all = new All($equalsNull, $equalsNotNull);
        $allOnlyNull = new All($equalsNull);

        $this->assertEquals([], $equalsNull->toArray());
        $this->assertEquals(['=', 'test', 1], $equalsNotNull->toArray());
        $this->assertEquals(['and', ['=', 'test', 1]], $all->toArray());
        $this->assertEquals([], $allOnlyNull->toArray());
    }
}
