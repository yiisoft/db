<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder;

use Yiisoft\Db\Schema\Column\ColumnInterface;

interface ColumnDefinitionBuilderInterface
{
    /**
     * Builds column definition based on given column instance.
     *
     * @param ColumnInterface $column the column instance which should be converted into a string representation.
     *
     * @return string the column SQL definition.
     */
    public function build(ColumnInterface $column): string;

    /**
     * Builds column definition for `ALTER` operation based on given column instance.
     */
    public function buildAlter(ColumnInterface $column): string;
}
