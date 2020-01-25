<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraints;

/**
 * CheckConstraint represents the metadata of a table `CHECK` constraint.
 */
class CheckConstraint extends Constraint
{
    /**
     * @var string the SQL of the `CHECK` constraint.
     */
    private string $expression;

    public function getExpression(): string
    {
        return $this->expression;
    }

    public function setExpression(string $value): void
    {
        $this->expression = $value;
    }
}
