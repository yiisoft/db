<?php

declare(strict_types=1);

namespace Yiisoft\Db\DataReader\Filter;

use Yiisoft\Data\Reader\Filter\FilterInterface;


abstract class CompareFilter implements FilterInterface
{
    protected string $_column;
    protected $_value;

     /**
     * @var bool
     */
    protected bool $_ignoreNull = false;


    public function __construct(string $column, $value, ?string $table = null)
    {
        $this->_value = $value;

        if ($table) {
            $this->_column = $table . '.' . $column;
        } else {
            $this->_column = $column;
        }
    }

    public function withIgnoreNull(bool $ignoreNull = false)
    {
        $new = clone $this;
        $new->_ignoreNull = $ignoreNull;

        return $new;
    }

    public function getIgnoreNull(): bool
    {
        return $this->_ignoreNull;
    }

    public function toArray(): array
    {
        if ($this->_value === null) {
            return ['IS', $this->_column, null];
        }

        return [static::getOperator(), $this->_column , $this->_value];
    }
}
