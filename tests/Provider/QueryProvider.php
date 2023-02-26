<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Expression\Expression;

class QueryProvider
{
    public static function filterConditionData(): array
    {
        return [
            /* like */
            [['like', 'name', []], null],
            [['not like', 'name', []], null],
            [['or like', 'name', []],  null],
            [['or not like', 'name', []], null],

            /* not */
            [['not', ''], null],

            /* and */
            [['and', '', ''], null],
            [['and', '', 'id=2'], ['and', 'id=2']],
            [['and', 'id=1', ''], ['and', 'id=1']],
            [['and', 'type=1', ['or', '', 'id=2']], ['and', 'type=1', ['or', 'id=2']]],

            /* or */
            [['or', 'id=1', ''], ['or', 'id=1']],
            [['or', 'type=1', ['or', '', 'id=2']], ['or', 'type=1', ['or', 'id=2']]],

            /* between */
            [['between', 'id', 1, null], null],
            [['between', 'id'], null],
            [['between', 'id', 1], null],
            [['not between', 'id', null, 10], null],
            [['between', 'id', 1, 2], ['between', 'id', 1, 2]],

            /* in */
            [['in', 'id', []], null],
            [['not in', 'id', []], null],

            /* simple conditions */
            [['=', 'a', ''], null],
            [['>', 'a', ''], null],
            [['>=', 'a', ''], null],
            [['<', 'a', ''], null],
            [['<=', 'a', ''], null],
            [['<>', 'a', ''], null],
            [['!=', 'a', ''], null],
        ];
    }

    public static function normalizeOrderBy(): array
    {
        return [
            ['id', ['id' => 4]],
            [['id'], ['id']],
            ['name ASC, date DESC', ['name' => 4, 'date' => 3]],
            [new Expression('SUBSTR(name, 3, 4) DESC, x ASC'), [new Expression('SUBSTR(name, 3, 4) DESC, x ASC')]],
        ];
    }

    public static function normalizeSelect(): array
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
}
