<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler;

/**
 * Interface-decorator to work with `yiisoft\profiler` or another profiler.
 *
 * @see \Yiisoft\Db\Connection\ConnectionInterface::setProfiler()
 */
interface ProfilerInterface
{
    /**
     * Marks the beginning of a code block for profiling.
     *
     * There should be a matching call to {@see end()} with the same category name.
     *
     * The `begin()` and `end()` calls must also be properly nested.
     *
     * @param string $token Token for the code block.
     * @param array|ContextInterface $context The context data of this profile block.
     */
    public function begin(string $token, array|ContextInterface $context = []): void;

    /**
     * Marks the end of a code block for profiling.
     *
     * There should be a matching call to {@see begin()} with the same category name.
     *
     * The `begin()` and `end()` calls must also be properly nested.
     *
     * @param string $token Token for the code block.
     * @param array|ContextInterface $context The context data of this profile block.
     *
     * {@see begin()}
     */
    public function end(string $token, array|ContextInterface $context = []): void;
}
