<?php

declare(strict_types=1);

namespace Yiisoft\Db\QueryBuilder\Condition\Builder;

use Traversable;
use Yiisoft\Db\Expression\Param;
use Yiisoft\Db\Constant\DataType;
use Yiisoft\Db\Exception\Exception;
use InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\Builder\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\QueryBuilder\Condition\Like;
use Yiisoft\Db\QueryBuilder\Condition\LikeConjunction;
use Yiisoft\Db\QueryBuilder\Condition\LikeMode;
use Yiisoft\Db\QueryBuilder\Condition\NotLike;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function implode;
use function is_string;
use function str_contains;
use function strtr;

/**
 * Build an object of {@see Like} or {@see NotLike} into SQL expressions.
 *
 * @implements ExpressionBuilderInterface<Like|NotLike>
 */
class LikeBuilder implements ExpressionBuilderInterface
{
    /**
     * @var string SQL fragment to append to the end of `LIKE` conditions.
     */
    protected const ESCAPE_SQL = '';

    /**
     * @var array Map of chars to their replacements in `LIKE` conditions. By default, it's configured to escape
     * `%`, `_` and `\` with `\`.
     */
    protected array $escapingReplacements = [
        '%' => '\%',
        '_' => '\_',
        '\\' => '\\\\',
    ];

    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {
    }

    /**
     * Build SQL for {@see Like} or {@see NotLike}.
     *
     * @param Like|NotLike $expression
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $values = $expression->value;

        [$not, $operator] = $this->getOperatorData($expression);

        if ($values === null) {
            return $this->buildForEmptyValue($not);
        }

        if (is_iterable($values)) {
            if ($values instanceof Traversable) {
                $values = iterator_to_array($values);
            }
            if (empty($values)) {
                return $this->buildForEmptyValue($not);
            }
        } else {
            $values = [$values];
        }

        $column = $this->prepareColumn($expression, $params);

        $parts = [];
        foreach ($values as $value) {
            /** @var ExpressionInterface|int|string $value */
            $placeholderName = $this->preparePlaceholderName($value, $expression, $params);
            $parts[] = "$column $operator $placeholderName" . static::ESCAPE_SQL;
        }

        $conjunction = match ($expression->conjunction) {
            LikeConjunction::And => ' AND ',
            LikeConjunction::Or => ' OR ',
        };

        return implode($conjunction, $parts);
    }

    /**
     * Prepare column to use in SQL.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    protected function prepareColumn(Like|NotLike $condition, array &$params): string
    {
        $column = $condition->column;

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
        string|int|ExpressionInterface $value,
        Like|NotLike $condition,
        array &$params,
    ): string {
        if ($value instanceof ExpressionInterface) {
            return $this->queryBuilder->buildExpression($value, $params);
        }

        if (is_string($value) && $condition->escape) {
            $value = strtr($value, $this->escapingReplacements);
        }

        $value = match ($condition->mode) {
            LikeMode::Contains => '%' . $value . '%',
            LikeMode::StartsWith => $value . '%',
            LikeMode::EndsWith => '%' . $value,
            LikeMode::Custom => (string) $value,
        };

        return $this->queryBuilder->bindParam(new Param($value, DataType::STRING), $params);
    }

    /**
     * Get operator and `not` flag for the given condition.
     *
     * @psalm-return array{0: bool, 1: string}
     */
    protected function getOperatorData(Like|NotLike $condition): array
    {
        return match ($condition::class) {
            Like::class => [false, 'LIKE'],
            NotLike::class => [true, 'NOT LIKE'],
        };
    }

    private function buildForEmptyValue(bool $not): string
    {
        return $not ? '' : '0=1';
    }
}
