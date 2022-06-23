<?php

declare(strict_types=1);

namespace Yiisoft\Db\Driver;

interface DriverInterface
{
    /**
     * Returns the driver name.
     *
     * @return string The driver name DB connection.
     */
    public function getDriverName(): string;
}
