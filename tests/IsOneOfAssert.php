<?php
declare(strict_types=1);

namespace Yiisoft\Db\Tests;

use Yiisoft\VarDumper\VarDumper;

/**
 * IsOneOfAssert asserts that the value is one of the expected values.
 */
class IsOneOfAssert extends \PHPUnit\Framework\Constraint\Constraint
{
    private $allowedValues;

    /**
     * IsOneOfAssert constructor.
     * @param array $allowedValues
     */
    public function __construct(array $allowedValues)
    {
        $this->allowedValues = $allowedValues;
    }


    /**
     * Returns a string representation of the object.
     *
     * @return string
     */
    public function toString(): string
    {
        $allowedValues = array_map(function ($value) {
            return VarDumper::dumpAsString($value);
        }, $this->allowedValues);

        $expectedAsString = implode(', ', $allowedValues);

        return "is one of $expectedAsString";
    }

    /**
     * {@inheritdoc}
     */
    protected function matches($other): bool
    {
        return in_array($other, $this->allowedValues, false);
    }
}
