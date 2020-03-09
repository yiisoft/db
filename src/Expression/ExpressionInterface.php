<?php

declare(strict_types=1);

namespace Yiisoft\Db\Expression;

/**
 * Interface ExpressionInterface should be used to mark classes, that should be built in a special way.
 *
 * The database abstraction layer of Yii framework supports objects that implement this interface and will use
 * {@see ExpressionBuilderInterface} to build them.
 *
 * The default implementation is a class {@see Expression}.
 */
interface ExpressionInterface
{
}
