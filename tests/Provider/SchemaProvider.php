<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

final class SchemaProvider
{
    public function constraints(): array
    {
        $baseSchemaProvider = new BaseSchemaProvider();

        return $baseSchemaProvider->constraints();
    }

    public function pdoAttributes(): array
    {
        $baseSchemaProvider = new BaseSchemaProvider();

        return $baseSchemaProvider->pdoAttributes();
    }

    public function tableSchema(): array
    {
        $baseSchemaProvider = new BaseSchemaProvider();

        return $baseSchemaProvider->tableSchema();
    }

    public function tableSchemaCachePrefixes(): array
    {
        $baseSchemaProvider = new BaseSchemaProvider();

        return $baseSchemaProvider->tableSchemaCachePrefixes();
    }
}
