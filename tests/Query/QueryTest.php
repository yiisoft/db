<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Query;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractQueryTest;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 */
final class QueryTest extends AbstractQueryTest
{
    use TestTrait;

    public function testColumn(): void
    {
        $db = $this->getConnectionWithData();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        (new Query($db))->select('name')->from('customer')->orderBy(['id' => SORT_DESC])->column();
    }

    public function testCount(): void
    {
        $db = $this->getConnectionWithData();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        (new Query($db))->from('customer')->count();
    }

    public function testExists(): void
    {
        $db = $this->getConnectionWithData();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        (new Query($db))->from('customer')->where(['status' => 2])->exists();
    }

    public function testLimitOffsetWithExpression(): void
    {
        $db = $this->getConnectionWithData();

        $query = (new Query($db))->from('customer')->select('id')->orderBy('id');
        $query->limit(new Expression('1 + 1'))->offset(new Expression('1 + 0'));

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        $query->column();
    }

    public function testOne(): void
    {
        $db = $this->getConnectionWithData();

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stubs\Command does not support internalExecute() by core-db.'
        );

        (new Query($db))->from('customer')->where(['status' => 2])->one();
    }
}
