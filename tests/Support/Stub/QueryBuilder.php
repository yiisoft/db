<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stub;

use Yiisoft\Db\QueryBuilder\AbstractQueryBuilder;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class QueryBuilder extends AbstractQueryBuilder
{
    public function __construct(QuoterInterface $quoter, SchemaInterface $schema)
    {
        $ddlBuilder = new DDLQueryBuilder($this, $quoter, $schema);
        $dmlBuilder = new DMLQueryBuilder($this, $quoter, $schema);
        $dqlBuilder = new DQLQueryBuilder($this, $quoter, $schema);

        parent::__construct($quoter, $schema, $ddlBuilder, $dmlBuilder, $dqlBuilder);
    }
}
