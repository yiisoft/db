<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Tests\Support\TestTrait;

final class QueryBuilderProvider
{
    use TestTrait;

    public function addForeignKey(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addForeignKey();
    }

    public function addPrimaryKey(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addPrimaryKey();
    }

    public function addUnique(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addUnique();
    }

    public function batchInsert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->batchInsert();
    }

    public function buildCondition(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildCondition($this->getConnection());
    }

    public function buildFilterCondition(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildFilterCondition();
    }

    public function buildFrom(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildFrom();
    }

    public function buildLikeCondition(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildLikeCondition();
    }

    public function buildWhereExists(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildWhereExists();
    }

    public function createIndex(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->createIndex();
    }

    public function delete(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->delete();
    }

    public function insert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->insert($this->getConnection());
    }

    public function insertEx(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->insertEx($this->getConnection());
    }

    public function selectExist(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->selectExist();
    }

    public function update(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->update();
    }

    public function upsert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->upsert($this->getConnection());
    }
}
