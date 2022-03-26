<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use PDO;
use PDOStatement;
use Throwable;
use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Query\Data\DataReader;

abstract class CommandPdo extends Command
{
    private int $fetchMode = PDO::FETCH_ASSOC;

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
            $this->pdoStatement?->bindValue($name, $value->getValue(), $value->getType());
        }
    }

    public function bindParam(
        int|string $name,
        mixed      &$value,
        ?int       $dataType = null,
        ?int       $length = null,
        mixed      $driverOptions = null
    ): static
    {
        $this->prepare();

        if ($dataType === null) {
            $dataType = $this->queryBuilder()->schema()->getPdoType($value);
        }

        if ($length === null) {
            $this->pdoStatement?->bindParam($name, $value, $dataType);
        } elseif ($driverOptions === null) {
            $this->pdoStatement?->bindParam($name, $value, $dataType, $length);
        } else {
            $this->pdoStatement?->bindParam($name, $value, $dataType, $length, $driverOptions);
        }

        return $this;
    }

    /** план такой
     * + отделяем вариант с returnDataReader в отдльную функцию
     * + сам queryInternal дробим на части
     * в функции queryInternal собираем в единый вызов, используя queryInternal из нужного класса
     * Проблемы
     * + функция logQuery
     * общий элемент queryCache, но его можно вынести и доносить только параметры вызова duration и depedency
     * внутрь класса передать надо profiler, queryCache
     */

    protected function internalGetQueryResult(int $queryMode): mixed
    {
        if ($queryMode === static::QUERY_MODE_CURSOR) {
            return new DataReader($this);
        }

        if ($queryMode === static::QUERY_MODE_NONE) {
            return $this->pdoStatement?->rowCount() ?? 0;
        }

        if ($queryMode === static::QUERY_MODE_ROW) {
            $result = $this->pdoStatement?->fetch($this->fetchMode);
        } else {
            $result = $this->pdoStatement?->fetchAll($this->fetchMode);
        }
        $this->pdoStatement?->closeCursor();

        return $result;
    }

    /**
     * Executes a prepared statement.
     *
     * It's a wrapper around {@see PDOStatement::execute()} to support transactions and retry handlers.
     *
     * @param string|null $rawSql the rawSql if it has been created.
     *
     * @throws Exception|Throwable
     */
    abstract protected function internalExecute(?string $rawSql): void;
}
