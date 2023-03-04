<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Common;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Query\BatchQueryResult;
use Yiisoft\Db\Query\BatchQueryResultInterface;
use Yiisoft\Db\Query\Query;
use Yiisoft\Db\Tests\Support\TestTrait;

abstract class CommonBatchQueryResultTest extends TestCase
{
    use TestTrait;

    public function testBatchQueryResult(): void
    {
        // initialize property test
        $db = $this->getConnection(true);

        $query = new Query($db);
        $query->from('customer')->orderBy('id');
        $result = $query->batch(2);

        $this->assertInstanceOf(BatchQueryResultInterface::class, $result);
        $this->assertSame(2, $result->getBatchSize());
        $this->assertSame($result->getQuery(), $query);

        // normal query
        $query = new Query($db);
        $query->from('customer')->orderBy('id');
        $allRows = [];
        $batch = $query->batch(2);
        $step = 0;

        foreach ($batch as $rows) {
            $allRows = array_merge($allRows, $rows);
            $step++;
        }

        $this->assertCount(3, $allRows);
        $this->assertSame(2, $step);
        $this->assertSame('user1', $allRows[0]['name']);
        $this->assertSame('user2', $allRows[1]['name']);
        $this->assertSame('user3', $allRows[2]['name']);

        // rewind
        $allRows = [];
        $step = 0;

        foreach ($batch as $rows) {
            $allRows = array_merge($allRows, $rows);
            $step++;
        }

        $this->assertCount(3, $allRows);
        $this->assertSame(2, $step);

        $batch->reset();

        // empty query
        $query = new Query($db);
        $query->from('customer')->where(['id' => 100]);
        $allRows = [];
        $batch = $query->batch(2);

        foreach ($batch as $rows) {
            $allRows = array_merge($allRows, $rows);
        }

        $this->assertCount(0, $allRows);

        // query with index
        $query = new Query($db);
        $query->from('customer')->indexBy('name');
        $allRows = [];

        foreach ($query->batch(2) as $rows) {
            $allRows = array_merge($allRows, $rows);
        }

        $this->assertCount(3, $allRows);
        $this->assertSame('address1', $allRows['user1']['address']);
        $this->assertSame('address2', $allRows['user2']['address']);
        $this->assertSame('address3', $allRows['user3']['address']);

        // each
        $query = new Query($db);
        $query->from('customer')->orderBy('id');
        $allRows = $this->getAllRowsFromEach($query->each(2));

        $this->assertCount(3, $allRows);
        $this->assertSame('user1', $allRows[0]['name']);
        $this->assertSame('user2', $allRows[1]['name']);
        $this->assertSame('user3', $allRows[2]['name']);

        // each with key
        $query = new Query($db);
        $query->from('customer')->orderBy('id')->indexBy('name');
        $allRows = $this->getAllRowsFromEach($query->each(100));

        $this->assertCount(3, $allRows);
        $this->assertSame('address1', $allRows['user1']['address']);
        $this->assertSame('address2', $allRows['user2']['address']);
        $this->assertSame('address3', $allRows['user3']['address']);

        $db->close();
    }

    public function testBatchWithoutDbParameter(): void
    {
        $db = $this->getConnection(true);

        $query = new Query($db);
        $query = $query->from('customer')->orderBy('id')->limit(3);
        $customers = $this->getAllRowsFromBatch($query->batch(2));

        $this->assertCount(3, $customers);
        $this->assertEquals('user1', $customers[0]['name']);
        $this->assertEquals('user2', $customers[1]['name']);
        $this->assertEquals('user3', $customers[2]['name']);
    }

    public function testBatchWithIndexBy(): void
    {
        $db = $this->getConnection(true);

        $query = new Query($db);
        $query->from('customer')->orderBy('id')->limit(3)->indexBy('id');
        $customers = $this->getAllRowsFromBatch($query->batch(2));

        $this->assertCount(3, $customers);
        $this->assertEquals('user1', $customers[0]['name']);
        $this->assertEquals('user2', $customers[1]['name']);
        $this->assertEquals('user3', $customers[2]['name']);

        $db->close();
    }

    public function testBatchQueryResultWithoutPopulate(): void
    {
        $db = $this->getConnection(true);

        $query = new Query($db);
        $query->from('customer')->orderBy('id')->limit(3)->indexBy('id');

        $batchQueryResult = new BatchQueryResult($query);

        $customers = $this->getAllRowsFromBatch($batchQueryResult);

        $this->assertCount(3, $customers);
        $this->assertEquals('user1', $customers[0]['name']);
        $this->assertEquals('user2', $customers[1]['name']);
        $this->assertEquals('user3', $customers[2]['name']);
    }

    protected function getAllRowsFromBatch(BatchQueryResultInterface $batch): array
    {
        $allRows = [];

        foreach ($batch as $rows) {
            $allRows = array_merge($allRows, $rows);
        }

        return $allRows;
    }

    protected function getAllRowsFromEach(BatchQueryResultInterface $each): array
    {
        $allRows = [];

        foreach ($each as $index => $row) {
            $allRows[$index] = $row;
        }

        return $allRows;
    }
}
