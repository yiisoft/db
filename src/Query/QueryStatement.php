<?php

declare(strict_types=1);

namespace Yiisoft\Db\Query;

use Yiisoft\Db\Command\ParamInterface;

interface QueryStatement
{
    /**
     * Returns the SQL statement for this command.
     *
     * @return string The SQL statement to execute.
     */
    public function getSql(): string;

    /**
     * Specifies the SQL statement to execute.
     *
     * The previous SQL (if any) will be discarded, and {@see Param} will be cleared as well. See {@see reset()} for
     * details.
     *
     * @param string $sql The SQL statement to set.
     *
     * @see reset()
     * @see cancel()
     */
    public function setSql(string $sql): static;

    /**
     * Return the params used in the last query.
     *
     * @param bool $asValues By default, returns an array of name => value pairs. If set to `true`, returns an array of
     * {@see ParamInterface}.
     *
     * @psalm-return array|ParamInterface[]
     *
     * @return array The params used in the last query.
     */
    public function getParams(bool $asValues = true): array;

    /**
     * Specifies the params to be used in the query.
     *
     * @param array|ParamInterface[] $params Params to set {@see ParamInterface}.
     */
    public function setParams(array $params): static;
}
