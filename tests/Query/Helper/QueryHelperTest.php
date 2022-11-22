<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Query\Helper;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Query\Helper\QueryHelper;
use Yiisoft\Db\Schema\Quoter;

final class QueryHelperTest extends TestCase
{
    public function tablesNameDataProvider(): array
    {
        return [
            [['customer'], '', ['{{customer}}' => '{{customer}}']],
            [['profile AS "prf"'], '', ['{{prf}}' => '{{profile}}']],
            [['mainframe as400'], '', ['{{as400}}' => '{{mainframe}}']],
            [
                ['x' => new Expression('(SELECT id FROM user)')],
                '',
                ['{{x}}' => new Expression('(SELECT id FROM user)')],
            ],
        ];
    }

    /**
     * @dataProvider tablesNameDataProvider
     */
    public function testCleanUpTableNames(array $tables, string $prefixDatabase, array $expected): void
    {
        $connection = $this->createConnectionMock();

        $this->assertEquals(
            $expected,
            $this->createQueryHelper()->cleanUpTableNames($tables, new Quoter('"', '"'))
        );
    }

    public function testCleanUpTableNamesException(): void
    {
        $connection = $this->createConnectionMock();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('To use Expression in from() method, pass it in array format with alias.');
        $this->createQueryHelper()->cleanUpTableNames(
            [new Expression('(SELECT id FROM user)')],
            new Quoter('"', '"')
        );
    }

    public function filterConditionDataProvider(): array
    {
        return [
            /* like */
            [['like', 'name', []], []],
            [['not like', 'name', []], []],
            [['or like', 'name', []],  []],
            [['or not like', 'name', []], []],

            /* not */
            [['not', ''], []],

            /* and */
            [['and', '', ''], []],
            [['and', '', 'id=2'], ['and', 'id=2']],
            [['and', 'id=1', ''], ['and', 'id=1']],
            [['and', 'type=1', ['or', '', 'id=2']], ['and', 'type=1', ['or', 'id=2']]],

            /* or */
            [['or', 'id=1', ''], ['or', 'id=1']],
            [['or', 'type=1', ['or', '', 'id=2']], ['or', 'type=1', ['or', 'id=2']]],

            /* between */
            [['between', 'id', 1, null], []],
            [['not between', 'id', null, 10], []],

            /* in */
            [['in', 'id', []], []],
            [['not in', 'id', []], []],

            /* simple conditions */
            [['=', 'a', ''], []],
            [['>', 'a', ''], []],
            [['>=', 'a', ''], []],
            [['<', 'a', ''], []],
            [['<=', 'a', ''], []],
            [['<>', 'a', ''], []],
            [['!=', 'a', ''], []],
        ];
    }

    /**
     * @dataProvider filterConditionDataProvider
     */
    public function testFilterCondition(array|string $condition, array|string $expected): void
    {
        $this->assertEquals($expected, $this->createQueryHelper()->filterCondition($condition));
    }

    public function normalizeOrderByProvider(): array
    {
        return [
            ['id', ['id' => 4]],
            [['id'], ['id']],
            ['name ASC, date DESC', ['name' => 4, 'date' => 3]],
            [new Expression('SUBSTR(name, 3, 4) DESC, x ASC'), [new Expression('SUBSTR(name, 3, 4) DESC, x ASC')]],
        ];
    }

    /**
     * @dataProvider normalizeOrderByProvider
     */
    public function testNormalizeOrderBy(array|string|Expression $columns, array|string $expected): void
    {
        $this->assertEquals($expected, $this->createQueryHelper()->normalizeOrderBy($columns));
    }

    public function normalizeSelectProvider(): array
    {
        return [
            ['exists', ['exists' => 'exists']],
            ['count(*) > 1', ['count(*) > 1']],
            ['name, name, name as X, name as X', ['name' => 'name', 'X' => 'name']],
            [
                ['email', 'address', 'status' => new Expression('1')],
                ['email' => 'email', 'address' => 'address', 'status' => new Expression('1')],
            ],
            [new Expression('1 as Ab'), [new Expression('1 as Ab')]],
        ];
    }

    /**
     * @dataProvider normalizeSelectProvider
     */
    public function testNormalizeSelect(array|string|Expression $columns, array|string $expected): void
    {
        $this->assertEquals($expected, $this->createQueryHelper()->normalizeSelect($columns));
    }

    private function createQueryHelper(): QueryHelper
    {
        return new QueryHelper();
    }

    private function createConnectionMock(): ConnectionInterface
    {
        return $this->createMock(ConnectionInterface::class);
    }
}
