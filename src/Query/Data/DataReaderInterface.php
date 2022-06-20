<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Data;

use Countable;
use Iterator;

/**
 * DataReader represents a forward-only stream of rows from a query result set.
 *
 * To read the current row of data, call {@see read()}. The method {@see readAll()} returns all the rows in a single
 * array. Rows of data can also be read by iterating through the reader. For example,
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post');
 * $reader = $command->query();
 *
 * while ($row = $reader->read()) {
 *     $rows[] = $row;
 * }
 *
 * // equivalent to:
 * foreach ($reader as $row) {
 *     $rows[] = $row;
 * }
 *
 * Note that since DataReader is a forward-only stream, you can only traverse it once. Doing it the second time will
 * throw an exception.
 */
interface DataReaderInterface extends Iterator, Countable
{
}
