<?php

declare(strict_types=1);

use Yiisoft\Db\Cache\SchemaCache;

/**
 * @psalm-var array{
 *     yiisoft/db: array{
 *         schema-cache: array{
 *             enabled?: bool
 *         }
 *     }
 * } $params
 */

return [
    SchemaCache::class => [
        'class' => SchemaCache::class,
        'setEnabled()' => [$params['yiisoft/db']['schema-cache']['enabled'] ?? true],
    ],
];
