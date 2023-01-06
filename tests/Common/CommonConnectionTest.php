<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Tests\AbstractConnectionTest;

abstract class CommonConnectionTest extends AbstractConnectionTest
{
    /**
     * @throws InvalidConfigException
     * @throws Throwable
     */
    public function testTransactionShortcutException(): void
    {
        $db = $this->getConnection(true);

        $this->expectException(Exception::class);

        $db->transaction(
            static function () use ($db) {
                $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();

                throw new Exception('Exception in transaction shortcut');
            }
        );
        $profilesCount = $db->createCommand(
            <<<SQL
            SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'
            SQL
        )->queryScalar();

        $this->assertSame(0, $profilesCount, 'profile should not be inserted in transaction shortcut');

        $db->close();
    }
}
