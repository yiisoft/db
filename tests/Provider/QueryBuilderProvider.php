<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Provider;

use Yiisoft\Db\Tests\Support\TestTrait;

final class QueryBuilderProvider
{
    use TestTrait;

    public function addColumn(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addColumn();
    }

    public function addDropChecks(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addDropChecks();
    }

    public function addDropForeignKeys(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addDropForeignKeys();
    }

    public function addDropPrimaryKeys(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addDropPrimaryKeys();
    }

    public function addDropUniques(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->addDropUniques();
    }

    public function alterColumn(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->alterColumn($this->getConnection());
    }

    public function batchInsert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->batchInsert($this->getConnection());
    }

    public function buildConditions(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildConditions($this->getConnection());
    }

    public function buildFilterConditions(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildFilterConditions($this->getConnection());
    }

    public function buildFrom(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildFrom();
    }

    public function buildLikeConditions(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildLikeConditions($this->getConnection());
    }

    public function buildWhereExists(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->buildWhereExists($this->getConnection());
    }

    public function createDropIndex(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->createDropIndex();
    }

    public function delete(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->delete($this->getConnection());
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

        return $baseQueryBuilderProvider->update($this->getConnection());
    }

    public function upsert(): array
    {
        $baseQueryBuilderProvider = new BaseQueryBuilderProvider();

        return $baseQueryBuilderProvider->upsert($this->getConnection());
    }
}
