<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Tests\Support\Mock;

final class QueryBuilderProvider
{
    public function addDropChecks(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->addDropChecks();
    }

    public function addDropForeignKeys(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->addDropForeignKeys();
    }

    public function addDropPrimaryKeys(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->addDropPrimaryKeys();
    }

    public function addDropUniques(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->addDropUniques();
    }

    public function alterColumn(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->alterColumn();
    }

    public function batchInsert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->batchInsert();
    }

    public function buildConditions(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->buildConditions();
    }

    public function buildFilterCondition(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->buildFilterCondition();
    }

    public function buildFrom(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->buildFrom();
    }

    public function buildWhereExists(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->buildWhereExists();
    }

    public function createDropIndex(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->createDropIndex();
    }

    public function delete(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider(new Mock());

        return $baseQueryBuilderProvider->delete();
    }
}
