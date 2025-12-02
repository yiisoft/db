<?php

declare(strict_types=1);

use Yiisoft\Db\Cache\SchemaCache;

/** @var array $params */

return [
    SchemaCache::class => [
        'class' => SchemaCache::class,
        'setEnabled()' => [$params['yiisoft/db']['schema-cache']['enabled'] ?? true],
    ],
];
