<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Tests\AbstractConnectionTest;

abstract class CommonConnectionTest extends AbstractConnectionTest
{
    public function testTransactionShortcutException(): void
    {
        $db = $this->getConnection(true);

        $callable = static function () use ($db) {
            $db->createCommand()->insert('profile', ['description' => 'test transaction shortcut'])->execute();
            throw new Exception('Exception in transaction shortcut');
        };

        $exception = null;
        try {
            $db->transaction($callable);
        } catch (Throwable $exception) {
        }

        $this->assertInstanceOf(Exception::class, $exception);

        $db->close();
    }
}
