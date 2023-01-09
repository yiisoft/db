<?php

declare(strict_types=1);

namespace Yiisoft\Db\Profiler;

trait ProfilerAwareTrait
{
    protected ProfilerInterface|null $profiler = null;

    public function setProfiler(ProfilerInterface|null $profiler): void
    {
        $this->profiler = $profiler;
    }
}
