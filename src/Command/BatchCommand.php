<?php

declare(strict_types=1);

namespace Yiisoft\Db\Command;

use Yiisoft\Db\Exception\Exception;

/**
 * Object used as batch commands container
 */
final class BatchCommand
{
    /**
     * @param CommandInterface[] $commands Query statements for execution
     */
    public function __construct(private readonly array $commands)
    {
    }

    public function count(): int
    {
        return count($this->commands);
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @throws \Throwable
     * @throws Exception
     */
    public function execute(): int
    {
        $total = 0;
        foreach ($this->commands as $command) {
            $total += $command->execute();
        }

        return $total;
    }
}
