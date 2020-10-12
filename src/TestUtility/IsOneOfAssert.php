<?php

declare(strict_types=1);

namespace Yiisoft\Db\TestUtility;

use PHPUnit\Framework\Constraint\Constraint;
use Yiisoft\VarDumper\VarDumper;

use function array_map;
use function implode;
use function in_array;

/**
 * IsOneOfAssert asserts that the value is one of the expected values.
 */
final class IsOneOfAssert extends Constraint
{
    private array $allowedValues;

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
        $allowedValues = array_map(static function ($value) {
            return VarDumper::create($value)->asString();
        }, $this->allowedValues);

        $expectedAsString = implode(', ', $allowedValues);

        return "is one of $expectedAsString";
    }

    protected function matches($other): bool
    {
        return in_array($other, $this->allowedValues, false);
    }
}
