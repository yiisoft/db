<?php

declare(strict_types=1);

namespace yiiunit\framework\db\pgsql;

use Yiisoft\Db\Schemas\ColumnSchemaBuilder;
use Yiisoft\Db\Tests\ColumnSchemaBuilderTest as AbstractColumnSchemaBuilderTest;

final class ColumnSchemaBuilderTest extends AbstractColumnSchemaBuilderTest
{
    public ?string $driverName = 'pgsql';

    /**
     * @param string $type
     * @param int $length
     *
     * @return ColumnSchemaBuilder
     */
    public function getColumnSchemaBuilder($type, $length = null)
    {
        return new ColumnSchemaBuilder($type, $length, $this->getConnection());
    }
}
