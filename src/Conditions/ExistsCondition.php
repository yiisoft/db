<?php
declare(strict_types=1);

namespace Yiisoft\Db\Conditions;

use Yiisoft\Db\Query;

/**
 * Condition that represents `EXISTS` operator.
 */
class ExistsCondition implements ConditionInterface
{
    /**
     * @var string the operator to use (e.g. `EXISTS` or `NOT EXISTS`)
     */
    private $operator;

    /**
     * @var Query the {@see Query} object representing the sub-query.
     */
    private $query;

    /**
     * ExistsCondition constructor.
     *
     * @param string $operator the operator to use (e.g. `EXISTS` or `NOT EXISTS`)
     * @param Query  $query the {@see Query} object representing the sub-query.
     */
    public function __construct($operator, $query)
    {
        $this->operator = $operator;
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArrayDefinition($operator, $operands)
    {
        if (!isset($operands[0]) || !$operands[0] instanceof Query) {
            throw new \InvalidArgumentException('Subquery for EXISTS operator must be a Query object.');
        }

        return new static($operator, $operands[0]);
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @return Query
     */
    public function getQuery()
    {
        return $this->query;
    }
}
