<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use PDOStatement;

trait CommandPdoTrait
{
    /**
     * @var ParamInterface[]
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

//    public function bindParam(int|string $name, mixed &$value, ?int $dataType = null, ?int $length = null, mixed $driverOptions = null): self
//    {
//        $this->prepare();
//
//        if ($dataType === null) {
//            $dataType = $this->queryBuilder()->schema()->getPdoType($value);
//        }
//
//        if ($length === null) {
//            $this->pdoStatement->bindParam($name, $value, $dataType);
//        } elseif ($driverOptions === null) {
//            $this->pdoStatement->bindParam($name, $value, $dataType, $length);
//        } else {
//            $this->pdoStatement->bindParam($name, $value, $dataType, $length, $driverOptions);
//        }
//
//        $this->params[$name] = &$value;
//
//        return $this;
//    }
}
