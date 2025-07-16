<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Yiisoft\Db\Command\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Interface\LikeConditionInterface;
use Yiisoft\Db\QueryBuilder\Condition\LikeCondition;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function implode;
use function is_array;
use function preg_match;
use function str_contains;
use function strtoupper;
use function strtr;

/**
 * Build an object of {@see LikeConditionInterface} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<LikeConditionInterface>
 */
class LikeConditionBuilder implements ExpressionBuilderInterface
{
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
        private readonly string|null $escapeSql = null
    ) {
    }

    /**
     * @var array Map of chars to their replacements in `LIKE` conditions. By default, it's configured to escape
     * `%`, `_` and `\` with `\`.
     */
    protected array $escapingReplacements = [
        '%' => '\%',
        '_' => '\_',
        '\\' => '\\\\',
    ];

    /**
     * Build SQL for {@see LikeConditionInterface}.
     *
     * @param LikeConditionInterface $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $values = $expression->getValue();
        $escape = $expression->getEscapingReplacements();

        if ($escape === []) {
            $escape = $this->escapingReplacements;
        }

        [$andor, $not, $operator] = $this->parseOperator($expression);

        if (!is_array($values)) {
            $values = [$values];
        }

        if (empty($values)) {
            return $not ? '' : '0=1';
        }

        $column = $this->prepareColumn($expression, $params);

        $parts = [];

        /** @psalm-var list<string|ExpressionInterface> $values */
        foreach ($values as $value) {
            $placeholderName = $this->preparePlaceholderName($value, $expression, $escape, $params);
            $parts[] = "$column $operator $placeholderName$this->escapeSql";
        }

        return implode($andor, $parts);
    }

    /**
     * Prepare column to use in SQL.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    protected function prepareColumn(LikeConditionInterface $expression, array &$params): string
    {
        $column = $expression->getColumn();

        if ($column instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($column, $params);
        }

        if (!str_contains($column, '(')) {
            return $this->queryBuilder->getQuoter()->quoteColumnName($column);
        }

        return $column;
    }

    /**
     * Prepare value to use in SQL.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     * @return string
     */
    protected function preparePlaceholderName(
        string|ExpressionInterface $value,
        LikeConditionInterface $expression,
        array|null $escape,
        array &$params,
    ): string {
        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }
        return $this->queryBuilder->bindParam(
            new Param($escape === null ? $value : ('%' . strtr($value, $escape) . '%'), DataType::STRING),
            $params
        );
    }

    /**
     * Parses operator and returns its parts.
     *
     * @throws InvalidArgumentException
     *
     * @psalm-return array{0: string, 1: bool, 2: string}
     */
    protected function parseOperator(LikeConditionInterface $expression): array
    {
        $operator = strtoupper($expression->getOperator());
        if (!preg_match('/^(AND |OR |)((NOT |)I?LIKE)/', $operator, $matches)) {
            throw new InvalidArgumentException("Invalid operator in like condition: \"$operator\"");
        }

        $andor = ' ' . (!empty($matches[1]) ? $matches[1] : 'AND ');
        $not = !empty($matches[3]);
        $operator = $matches[2];

        return [$andor, $not, $operator];
    }
}
