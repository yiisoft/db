<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression\Value\Builder;

use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\Value\Identifier;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;
use Yiisoft\Db\Schema\IdentifierQuoterInterface;

use function sprintf;

/**
 * Builder for {@see Identifier} expressions.
 *
 * This builder converts {@see Identifier} expressions into strictly quoted identifiers using
 * {@see IdentifierQuoterInterface::quoteIdentifier()} of the query builder quoter.
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

        return $quoter->quoteIdentifier(...$expression->parts);
    }
}
