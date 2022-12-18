<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Connection;

use Yiisoft\Db\Tests\AbstractConnectionPDOTest;
use Yiisoft\Db\Tests\Support\TestTrait;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ConnectionPDOTest extends AbstractConnectionPDOTest
{
    use TestTrait;
}
