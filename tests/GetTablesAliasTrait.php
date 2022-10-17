<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Query;

trait GetTablesAliasTrait
{
    public function testGetTableNamesIsFromArrayWithAlias(): void
    {
        $query = new Query($this->getConnection());

        $query->from([
            'prf' => 'profile',
            '{{usr}}' => '{{user}}',
            '{{a b}}' => '{{c d}}',
            'post AS p',
        ]);

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(
            [
                '{{prf}}' => '{{profile}}',
                '{{usr}}' => '{{user}}',
                '{{a b}}' => '{{c d}}',
                '{{p}}' => '{{post}}',
            ],
            $tables
        );
    }

    public function testGetTableNamesIsFromArrayWithoutAlias(): void
    {
        $query = new Query($this->getConnection());

        $query->from([
            '{{profile}}',
            'user',
        ]);

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(['{{profile}}' => '{{profile}}', '{{user}}' => '{{user}}'], $tables);
    }

    public function testGetTableNamesIsFromString(): void
    {
        $query = new Query($this->getConnection());

        $query->from('profile AS \'prf\', user "usr", `order`, "customer", "a b" as "c d"');

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(
            [
                '{{prf}}' => '{{profile}}',
                '{{usr}}' => '{{user}}',
                '{{order}}' => '{{order}}',
                '{{customer}}' => '{{customer}}',
                '{{c d}}' => '{{a b}}',
            ],
            $tables
        );
    }

    public function testGetTablesAliasesFromString(): void
    {
        $query = new Query($this->getConnection());

        $query->from('profile AS \'prf\', user "usr", service srv, order, [a b] [c d], {{something}} AS myalias');

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(
            [
                '{{prf}}' => '{{profile}}',
                '{{usr}}' => '{{user}}',
                '{{srv}}' => '{{service}}',
                '{{order}}' => '{{order}}',
                '{{c d}}' => '{{a b}}',
                '{{myalias}}' => '{{something}}',
            ],
            $tables
        );
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/14150}
     */
    public function testGetTableNamesIsFromPrefixedTableName(): void
    {
        $query = new Query($this->getConnection());

        $query->from('{{%order_item}}');

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(['{{%order_item}}' => '{{%order_item}}'], $tables);
    }

    /**
     * {@see https://github.com/yiisoft/yii2/issues/14211}}
     */
    public function testGetTableNamesIsFromTableNameWithDatabase(): void
    {
        $query = new Query($this->getConnection());

        $query->from('tickets.workflows');

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(['{{tickets.workflows}}' => '{{tickets.workflows}}'], $tables);
    }

    public function testGetTableNamesIsFromAliasedExpression(): void
    {
        $query = new Query($this->getConnection());

        $expression = new Expression('(SELECT id FROM user)');

        $query->from($expression);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('To use Expression in from() method, pass it in array format with alias.');

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(['{{x}}' => $expression], $tables);
    }

    public function testGetTableNamesIsFromAliasedArrayWithExpression(): void
    {
        $query = new Query($this->getConnection());

        $query->from(['x' => new Expression('(SELECT id FROM user)')]);

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals(['{{x}}' => '(SELECT id FROM user)'], $tables);
    }

    public function testGetTableNamesIsFromAliasedSubquery(): void
    {
        $query = new Query($this->getConnection());

        $subQuery = new Query($this->getConnection());

        $subQuery->from('user');
        $query->from(['x' => $subQuery]);

        $expected = ['{{x}}' => $subQuery];

        $tables = $query->getTablesUsedInFrom();

        $this->assertEquals($expected, $tables);
    }
}
