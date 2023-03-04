<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler;

/**
 * Interface-decorator for work with `yiisoft\profiler` or other profiler.
 * Simple inject your implementation of current with using method {@see ConnectionInterface::setProfiler()}.
 */
interface ProfilerInterface
{
    /**
     * Marks the beginning of a code block for profiling.
     *
     * This has to be matched with a call to {@see end()} with the same category name.
     * The begin- and end- calls must also be properly nested.
     *
     * @param string $token Token for the code block.
     * @param array|ContextInterface $context The context data of this profile block.
     */
    public function begin(string $token, array|ContextInterface $context = []): void;

    /**
     * Marks the end of a code block for profiling.
     *
     * This has to be matched with a previous call to {@see begin()} with the same category name.
     *
     * @param string $token Token for the code block.
     * @param array|ContextInterface $context The context data of this profile block.
     *
     * {@see begin()}
     */
    public function end(string $token, array|ContextInterface $context = []): void;
}
