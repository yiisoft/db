<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler;

/**
 * Provides access to the database profiler, a tool for collecting and analyzing
 * database queries.
 *
 * This can be useful for debugging and optimizing database performance.
 */
trait ProfilerAwareTrait
{
    protected ?ProfilerInterface $profiler = null;

    public function setProfiler(?ProfilerInterface $profiler): void
    {
        $this->profiler = $profiler;
    }
}
