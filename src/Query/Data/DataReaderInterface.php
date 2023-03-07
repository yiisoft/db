<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query\Data;

use Countable;
use Iterator;

/**
 * This interface represents a forward-only stream of rows from a query result set.
 *
 * To read the current row of data just iterate on it.
 *
 * For example,
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post');
 * $reader = $command->query();
 *
 * foreach ($reader as $row) {
 *     $rows[] = $row;
 * }
 *
 * Note: That since DataReader is a forward-only stream, you can only traverse it once. Doing it the second time will
 * throw an exception.
 *
 * @extends Iterator<int|string, mixed>
 */
interface DataReaderInterface extends Iterator, Countable
{
}
