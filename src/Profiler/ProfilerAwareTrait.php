<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler;

/**
 * The ProfilerAwareTrait::class is a trait, allows a class to be aware of the database profiler component. When this
 * trait is used, the class will have access to the database profiler, which can be used to collect and analyze data
 * about database queries. This can be useful for debugging and optimizing database performance.
 */
trait ProfilerAwareTrait
{
    protected ProfilerInterface|null $profiler = null;

    public function setProfiler(ProfilerInterface|null $profiler): void
    {
        $this->profiler = $profiler;
    }
}
