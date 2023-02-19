<?php

declare(strict_types=1);

namespace Yiisoft\Db\Tests\Db\Command;

use PHPUnit\Framework\TestCase;
use Yiisoft\Db\Command\ParamBuilder;
use Yiisoft\Db\Expression\Expression;

/**
 * @group db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ParamBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $params = ['id' => 1, 'name' => 'test', 'expression' => new Expression('NOW()')];
        $expression = new Expression('id = :id AND name = :name AND expression = :expression', $params);
        $paramBuilder = new ParamBuilder();

        $this->assertSame(':pv3', $paramBuilder->build($expression, $params));
    }
}
