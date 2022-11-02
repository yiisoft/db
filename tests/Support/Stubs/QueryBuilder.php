<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Support\Stubs;

use Yiisoft\Db\QueryBuilder\DDLQueryBuilder;
use Yiisoft\Db\QueryBuilder\DDLQueryBuilderInterface;
use Yiisoft\Db\QueryBuilder\DMLQueryBuilder;
use Yiisoft\Db\QueryBuilder\DMLQueryBuilderInterface;
use Yiisoft\Db\QueryBuilder\DQLQueryBuilder;
use Yiisoft\Db\QueryBuilder\DQLQueryBuilderInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\QuoterInterface;
use Yiisoft\Db\Schema\SchemaInterface;

final class QueryBuilder extends \Yiisoft\Db\QueryBuilder\QueryBuilder implements QueryBuilderInterface
{
    public function __construct(QuoterInterface $quoter, SchemaInterface $schema)
    {
        $ddlBuilder = new class ($this, $quoter, $schema) extends DDLQueryBuilder implements DDLQueryBuilderInterface {
        };
        $dmlBuilder = new class ($this, $quoter, $schema) extends DMLQueryBuilder implements DMLQueryBuilderInterface {
        };
        $dqlBuilder = new class ($this, $quoter, $schema) extends DQLQueryBuilder implements DQLQueryBuilderInterface {
        };

        parent::__construct($quoter, $schema, $ddlBuilder, $dmlBuilder, $dqlBuilder);
    }
}
