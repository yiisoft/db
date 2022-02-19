<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use PDOStatement;

trait CommandPdoTrait
{
    /**
     * @psalm-var ParamInterface[]
     */
    protected array $params = [];

    protected ?PDOStatement $pdoStatement = null;

    public function getPdoStatement(): ?PDOStatement
    {
        return $this->pdoStatement;
    }

    public function cancel(): void
    {
        $this->pdoStatement = null;
    }

    /**
     * Binds pending parameters that were registered via {@see bindValue()} and {@see bindValues()}.
     *
     * Note that this method requires an active {@see pdoStatement}.
     */
    protected function bindPendingParams(): void
    {
        foreach ($this->params as $name => $value) {
            $this->pdoStatement->bindValue($name, $value->getValue(), $value->getType());
        }
    }
}
