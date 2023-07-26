<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;

/**
 * This interface defines the methods to build database expressions, such as conditions for a SELECT statement or values
 * to insert into a table.
 *
 * These methods include creating comparison operators (such as `=`, `>`, `<`), combining expressions with logical
 * operators (such as `AND`, `OR`), and building sub-queries.
 *
 * The interface provides a consistent way for developers to build expressions for various types of
 * database queries, without having to worry about the specific syntax of the underlying database.
 *
 * @see ExpressionInterface
 */
interface ExpressionBuilderInterface
{
    /**
     * Method builds the raw SQL from the $expression that will not be additionally escaped or quoted.
     *
     * @param ExpressionInterface $expression The expression to be built.
     * @param array $params the binding Parameters.
     *
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that will not be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string;
}
