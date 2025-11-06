<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Query;

use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractQueryTest;
use Yiisoft\Db\Tests\Support\Assert;
use Yiisoft\Db\Tests\Support\TestTrait;

use function PHPUnit\Framework\assertSame;

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
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.',
        );

        (new Query($db))->select('name')->from('customer')->orderBy(['id' => SORT_DESC])->column();
    }

    public function testCount(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.',
        );

        (new Query($db))->from('customer')->count();
    }

    public function testExists(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.',
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
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.',
        );

        $query->column();
    }

    public function testOne(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.',
        );

        (new Query($db))->from('customer')->where(['status' => 2])->one();
    }

    public function testColumnWithIndexBy(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(
            'Yiisoft\Db\Tests\Support\Stub\Command::internalExecute is not supported by this DBMS.',
        );

        parent::testColumnWithIndexBy();
    }

    public function testWithTypecasting(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db));

        $this->assertFalse(Assert::getPropertyValue($query, 'typecasting'));

        $query = $query->withTypecasting();

        $this->assertTrue(Assert::getPropertyValue($query, 'typecasting'));

        $query = $query->withTypecasting(false);

        $this->assertFalse(Assert::getPropertyValue($query, 'typecasting'));
    }

    public function testCreateCommandWithTypecasting(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db));
        $command = $query->createCommand();

        $this->assertFalse(Assert::getPropertyValue($command, 'phpTypecasting'));

        $command = $query->withTypecasting()->createCommand();

        $this->assertTrue(Assert::getPropertyValue($command, 'phpTypecasting'));
    }

    public static function dataFor(): iterable
    {
        yield 'null' => [[], null];
        yield 'empty-list' => [[], []];
        yield 'empty-string' => [[''], ''];
        yield 'string' => [['UPDATE'], 'UPDATE'];
        yield 'list' => [['UPDATE', 'SHARE'], ['UPDATE', 'SHARE']];
    }

    #[DataProvider('dataFor')]
    public function testFor(array $expected, array|string|null $value): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->for($value);

        assertSame($expected, $query->getFor());
    }

    public function testForTwice(): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->for('UPDATE');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The `FOR` part was set earlier. Use the `setFor()` or `addFor()` method.');
        $query->for('SHARE');
    }

    public static function dataAddFor(): iterable
    {
        yield 'null' => [['NO KEY UPDATE'], null];
        yield 'empty-list' => [['NO KEY UPDATE'], []];
        yield 'empty-string' => [['NO KEY UPDATE', ''], ''];
        yield 'string' => [['NO KEY UPDATE', 'UPDATE'], 'UPDATE'];
        yield 'list' => [['NO KEY UPDATE', 'UPDATE', 'SHARE'], ['UPDATE', 'SHARE']];
    }

    #[DataProvider('dataAddFor')]
    public function testAddFor(array $expected, array|string|null $value): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->for('NO KEY UPDATE')->addFor($value);

        assertSame($expected, $query->getFor());
    }

    public static function dataAddForOnly(): iterable
    {
        yield 'null' => [[], null];
        yield 'empty-list' => [[], []];
        yield 'empty-string' => [[''], ''];
        yield 'string' => [['UPDATE'], 'UPDATE'];
        yield 'list' => [['UPDATE', 'SHARE'], ['UPDATE', 'SHARE']];
    }

    #[DataProvider('dataAddForOnly')]
    public function testAddForOnly(array $expected, array|string|null $value): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->addFor($value);

        assertSame($expected, $query->getFor());
    }

    public static function dataSetFor(): iterable
    {
        yield 'null' => [[], null];
        yield 'empty-list' => [[], []];
        yield 'empty-string' => [[''], ''];
        yield 'string' => [['UPDATE'], 'UPDATE'];
        yield 'list' => [['UPDATE', 'SHARE'], ['UPDATE', 'SHARE']];
    }

    #[DataProvider('dataSetFor')]
    public function testSetFor(array $expected, array|string|null $value): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->for('NO KEY UPDATE')->setFor($value);

        assertSame($expected, $query->getFor());
    }

    public static function dataSetForOnly(): iterable
    {
        yield 'null' => [[], null];
        yield 'empty-list' => [[], []];
        yield 'empty-string' => [[''], ''];
        yield 'string' => [['UPDATE'], 'UPDATE'];
        yield 'list' => [['UPDATE', 'SHARE'], ['UPDATE', 'SHARE']];
    }

    #[DataProvider('dataSetForOnly')]
    public function testSetForOnly(array $expected, array|string|null $value): void
    {
        $db = $this->getConnection();

        $query = (new Query($db))->setFor($value);

        assertSame($expected, $query->getFor());
    }
}
