<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use function sprintf;

final class SchemaCacheProvider
{
    public function tablePrefixes(): array
    {
        $configs = [
            [
                'prefix' => '',
                'name' => 'type',
            ],
            [
                'prefix' => '',
                'name' => '{{%type}}',
            ],
            [
                'prefix' => 'ty',
                'name' => '{{%pe}}',
            ],
        ];

        $data = [];

        foreach ($configs as $config) {
            foreach ($configs as $testConfig) {
                if ($config === $testConfig) {
                    continue;
                }

                $description = sprintf(
                    "%s (with '%s' prefix) against %s (with '%s' prefix)",
                    $config['name'],
                    $config['prefix'],
                    $testConfig['name'],
                    $testConfig['prefix']
                );
                $data[$description] = [
                    $config['prefix'],
                    $config['name'],
                    $testConfig['prefix'],
                    $testConfig['name'],
                ];
            }
        }

        return $data;
    }
}
