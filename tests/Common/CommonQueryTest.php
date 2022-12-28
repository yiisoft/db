<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\AbstractQueryTest;

abstract class CommonQueryTest extends AbstractQueryTest
{
    /**
     * Ensure no ambiguous column error occurs on indexBy with JOIN.
     *
     * @link https://github.com/yiisoft/yii2/issues/13859
     */
    public function testAmbiguousColumnIndexBy()
    {
        $db = $this->getConnection(true);

        $selectExpression = match ($db->getName()) {
            'mysql' => "concat(customer.name,' in ', p.description) name",
            'oci' => "[[customer]].[[name]] || ' in ' || [[p]].[[description]] name",
            'pgsql', 'sqlite' => "(customer.name || ' in ' || p.description) AS name",
            'sqlsrv' => 'CONCAT(customer.name, \' in \', p.description) name',
        };

        $result = (new Query($db))
            ->select([$selectExpression])
            ->from('customer')
            ->innerJoin('profile p', '[[customer]].[[profile_id]] = [[p]].[[id]]')
            ->indexBy('id')
            ->column();

        $this->assertSame([1 => 'user1 in profile customer 1', 3 => 'user3 in profile customer 3'], $result);
    }
}
