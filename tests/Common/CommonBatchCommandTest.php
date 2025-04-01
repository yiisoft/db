<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\BatchCommand;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class CommonBatchCommandTest extends TestCase
{
    use TestTrait;

    public function testBatchQuery(): void
    {
        $db = $this->getConnection();

        $batchCommand = new BatchCommand($db);
        $batchCommand->addInsertBatchCommand(
            'customer',
            [['value1', 'value2']],
            ['column1', 'column2'],
        );
        $batchCommand->addInsertBatchCommand(
            'customer',
            [['value3', 'value4']],
            ['column1', 'column2'],
        );

        $this->assertSame(2, $batchCommand->count());
        $this->assertSame(0, $batchCommand->key());
        $batchCommand->next();
        $this->assertSame(1, $batchCommand->key());
        $batchCommand->rewind();
        $this->assertSame(0, $batchCommand->key());

        $db->close();
    }
}
