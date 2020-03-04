<?php

declare(strict_types=1);

namespace Yiisoft\Db\Constraints;

/**
 * CheckConstraint represents the metadata of a table `CHECK` constraint.
 */
class CheckConstraint extends Constraint
{
    private string $expression;

    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @param string $value the SQL of the `CHECK` constraint.
     *
     * @return void
     */
    public function setExpression(string $value): void
    {
        $this->expression = $value;
    }
}
