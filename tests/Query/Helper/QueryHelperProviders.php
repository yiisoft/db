<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Query\Helper;

use Yiisoft\Db\Expression\Expression;

final class QueryHelperProviders
{
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

    public function normalizeOrderByProvider(): array
    {
        return [
            ['id', ['id' => 4]],
            [['id'], ['id']],
            ['name ASC, date DESC', ['name' => 4, 'date' => 3]],
            [new Expression('SUBSTR(name, 3, 4) DESC, x ASC'), [new Expression('SUBSTR(name, 3, 4) DESC, x ASC')]],
        ];
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
}
