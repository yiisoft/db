<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\IntegrationTestCase;

abstract class CommonQueryGetTableAliasTest extends IntegrationTestCase
{
    public function testAliasesFromString(): void
    {
        $db = $this->getSharedConnection();

        $query = new Query($db);
        $query->from('profile AS \'prf\', user "usr", service srv, order, [a b] [c d], {{something}} AS myalias');
        $tables = $query->getTablesUsedInFrom();

        $this->assertSame(
            [
                '{{prf}}' => '{{profile}}',
                '{{usr}}' => '{{user}}',
                '{{srv}}' => '{{service}}',
                '{{order}}' => '{{order}}',
                '{{c d}}' => '{{a b}}',
                '{{myalias}}' => '{{something}}',
            ],
            $tables,
        );
    }

    public function testGetTableNamesIsFromAliasedSubquery(): void
    {
        $db = $this->getSharedConnection();

        $query = new Query($db);
        $subQuery = new Query($db);
        $subQuery->from('user');
        $query->from(['x' => $subQuery]);
        $expected = ['{{x}}' => $subQuery];
        $tables = $query->getTablesUsedInFrom();

        $this->assertSame($expected, $tables);
    }

    public function testNamesIsFromAliasedArrayWithExpression(): void
    {
        $db = $this->getSharedConnection();

        $query = new Query($db);
        $expression = new Expression('(SELECT id FROM user)');
        $query->from(['x' => $expression]);
        $tables = $query->getTablesUsedInFrom();

        $this->assertSame(['{{x}}' => $expression], $tables);
    }

    public function testNamesIsFromAliasedExpression(): void
    {
        $db = $this->getSharedConnection();

        $query = new Query($db);
        $expression = new Expression('(SELECT id FROM user)');
        $query->from($expression);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('To use Expression in from() method, pass it in array format with alias.');

        $tables = $query->getTablesUsedInFrom();

        $this->assertSame(['{{x}}' => $expression], $tables);
    }

    public function testNamesIsFromArrayWithAlias(): void
    {
        $db = $this->getSharedConnection();

        $query = new Query($db);
        $query->from(['prf' => 'profile', '{{usr}}' => '{{user}}', '{{a b}}' => '{{c d}}', 'post AS p']);

        $tables = $query->getTablesUsedInFrom();

        $this->assertSame(
            ['{{prf}}' => '{{profile}}', '{{usr}}' => '{{user}}', '{{a b}}' => '{{c d}}', '{{p}}' => '{{post}}'],
            $tables,
        );
    }

    public function testNamesIsFromArrayWithoutAlias(): void
    {
        $db = $this->getSharedConnection();

        $query = new Query($db);
        $query->from(['{{profile}}', 'user']);
        $tables = $query->getTablesUsedInFrom();

        $this->assertSame(['{{profile}}' => '{{profile}}', '{{user}}' => '{{user}}'], $tables);
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/14150
     */
    public function testNamesIsFromPrefixedTableName(): void
    {
        $db = $this->getSharedConnection();

        $query = new Query($db);
        $query->from('{{%order_item}}');
        $tables = $query->getTablesUsedInFrom();

        $this->assertSame(['{{%order_item}}' => '{{%order_item}}'], $tables);
    }

    public function testNamesIsFromString(): void
    {
        $db = $this->getSharedConnection();

        $query = new Query($db);
        $query->from('profile AS \'prf\', user "usr", `order`, "customer", "a b" as "c d"');
        $tables = $query->getTablesUsedInFrom();

        $this->assertSame(
            [
                '{{prf}}' => '{{profile}}',
                '{{usr}}' => '{{user}}',
                '{{order}}' => '{{order}}',
                '{{customer}}' => '{{customer}}',
                '{{c d}}' => '{{a b}}',
            ],
            $tables,
        );
    }

    /**
     * @link https://github.com/yiisoft/yii2/issues/14211
     */
    public function testNamesIsFromTableNameWithDatabase(): void
    {
        $db = $this->getSharedConnection();

        $query = new Query($db);
        $query->from('tickets.workflows');
        $tables = $query->getTablesUsedInFrom();

        $this->assertSame(['{{tickets.workflows}}' => '{{tickets.workflows}}'], $tables);
    }
}
