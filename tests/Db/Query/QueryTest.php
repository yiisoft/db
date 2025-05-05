<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Query;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractQueryTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class QueryTest extends AbstractQueryTest
{
    use TestTrait;

    public function testColumn(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        (new Query($db))->select('name')->from('customer')->orderBy(['id' => SORT_DESC])->column();
    }

    public function testCount(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        (new Query($db))->from('customer')->count();
    }

    public function testExists(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        (new Query($db))->from('customer')->where(['status' => 2])->exists();
    }

    public function testLimitOffsetWithExpression(): void
    {
        $db = $this->getConnection(true);

        $query = (new Query($db))->from('customer')->select('id')->orderBy('id');
        $query->limit(new Expression('1 + 1'))->offset(new Expression('1 + 0'));

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        $query->column();
    }

    public function testOne(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        (new Query($db))->from('customer')->where(['status' => 2])->one();
    }

    public function testColumnWithIndexBy(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.'
        );

        parent::testColumnWithIndexBy();
    }

    public function testWithTypecasting(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db));

        $this->assertFalse(Assert::getInaccessibleProperty($query, 'typecasting'));

        $query = $query->withTypecasting();

        $this->assertTrue(Assert::getInaccessibleProperty($query, 'typecasting'));

        $query = $query->withTypecasting(false);

        $this->assertFalse(Assert::getInaccessibleProperty($query, 'typecasting'));
    }

    public function testCreateCommandWithTypecasting(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db));
        $command = $query->createCommand();

        $this->assertFalse(Assert::getInaccessibleProperty($command, 'phpTypecasting'));

        $command = $query->withTypecasting()->createCommand();

        $this->assertTrue(Assert::getInaccessibleProperty($command, 'phpTypecasting'));
    }
}
