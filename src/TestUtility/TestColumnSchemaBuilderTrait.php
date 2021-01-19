<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

use Yiisoft\Db\Expression\Expression;
use Yiisoft\Db\Schema\ColumnSchemaBuilder;
use Yiisoft\Db\Schema\Schema;

use function array_shift;
use function call_user_func_array;

trait TestColumnSchemaBuilderTrait
{
    /**
     * @param string $type
     * @param int|null $length
     *
     * @return ColumnSchemaBuilder
     */
    public function getColumnSchemaBuilder(string $type, ?int $length = null): ColumnSchemaBuilder
    {
        return new ColumnSchemaBuilder($type, $length);
    }

    /**
     * @param string $expected
     * @param string $type
     * @param int|null $length
     * @param array $calls
     */
    public function checkBuildString(string $expected, string $type, ?int $length, $calls): void
    {
        $builder = $this->getColumnSchemaBuilder($type, $length);

        foreach ($calls as $call) {
            $method = array_shift($call);
            call_user_func_array([$builder, $method], $call);
        }

        self::assertEquals($expected, $builder->__toString());
    }

    public function typesProviderTrait(): array
    {
        return [
            ['integer NULL DEFAULT NULL', Schema::TYPE_INTEGER, null, [
                ['unsigned'], ['null'],
            ]],
            ['integer(10)', Schema::TYPE_INTEGER, 10, [
                ['unsigned'],
            ]],
            ['timestamp() WITH TIME ZONE NOT NULL', 'timestamp() WITH TIME ZONE', null, [
                ['notNull'],
            ]],
            ['timestamp() WITH TIME ZONE DEFAULT NOW()', 'timestamp() WITH TIME ZONE', null, [
                ['defaultValue', new Expression('NOW()')],
            ]],
            ['integer(10)', Schema::TYPE_INTEGER, 10, [
                ['comment', 'test'],
            ]],
        ];
    }
}
