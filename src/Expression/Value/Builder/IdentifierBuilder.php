<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

use InvalidArgumentException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Value\Identifier;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\IdentifierQuoterInterface;
use Yiisoft\Db\Schema\QuoterInterface;

use function sprintf;

/**
 * Builder for {@see Identifier} expressions.
 *
 * This builder converts {@see Identifier} expressions into strictly quoted identifiers using
 * {@see IdentifierQuoterInterface::quoteIdentifier()} of the query builder quoter.
 *
 * The following can't be used in name parts and cause `InvalidArgumentException`:
 *
 * - `[[...]]`, for example, `[[name]]`;
 * - `{{...}}`, for example, `{{table}}` or `{{%table}}`;
 * - for MSSQL, `[[...]`, for example, `[[name]`;
 * - for MSSQL, `[...]` at the beginning of a part, for example, `[name]`.
 *
 * Here `...` is a sequence of letters, digits, underscores, hyphens, dots and spaces.
 *
 * @implements ExpressionBuilderInterface<Identifier>
 */
final class IdentifierBuilder implements ExpressionBuilderInterface
{
    /**
     * @param QueryBuilderInterface $queryBuilder The query builder instance.
     */
    public function __construct(
        private readonly QueryBuilderInterface $queryBuilder,
    ) {}

    /**
     * @throws InvalidArgumentException If the quoted identifier contains sequences processed by
     * {@see QuoterInterface::quoteSql()}.
     * @throws NotSupportedException If the quoter doesn't implement {@see IdentifierQuoterInterface}.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        $quoter = $this->queryBuilder->getQuoter();

        if (!$quoter instanceof IdentifierQuoterInterface) {
            throw new NotSupportedException(
                sprintf(
                    'Quoter "%s" must implement "%s" to build "%s" expressions.',
                    $quoter::class,
                    IdentifierQuoterInterface::class,
                    Identifier::class,
                ),
            );
        }

        $sql = $quoter->quoteIdentifier(...$expression->parts);

        if ($quoter->quoteSql($sql) !== $sql) {
            throw new InvalidArgumentException(
                sprintf(
                    'Identifier %s must not contain sequences processed by "%s::quoteSql()", such as "[[...]]" or "{{...}}".',
                    $sql,
                    QuoterInterface::class,
                ),
            );
        }

        return $sql;
    }
}
