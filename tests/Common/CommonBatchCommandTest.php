<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\BatchCommand;
use Yiisoft\Db\Query\BatchQueryResult;
use Yiisoft\Db\Query\BatchQueryResultInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class CommonBatchCommandTest extends TestCase
{
    use TestTrait;

    public function testBatchQueryResult(): void
    {
        // initialize property test
        $db = $this->getConnection(true);
        $command = $db->createCommand();

        $batchCommand = $command->insertBatch(
            'customer',
            [['value1', 'value2'], ['value3', 'value4']],
            ['column1', 'column2'],
            1
        );

        $this->assertInstanceOf(BatchCommand::class, $batchCommand);
        $this->assertSame(2, $batchCommand->count());

        $this->assertSame(0, $batchCommand->key());
        $batchCommand->next();
        $this->assertSame(1, $batchCommand->key());
        $batchCommand->rewind();
        $this->assertSame(0, $batchCommand->key());

        $db->close();
    }

}
