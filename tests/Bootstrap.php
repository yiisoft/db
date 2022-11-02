<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Yiisoft\Db\Tests\Support\Mock;

$mock = new Mock();
$db = $mock->connection();
$mock->prepareDatabase($db);
$db->close();
