<?php

declare(strict_types=1);

namespace Yiisoft\Db\AwareTrait;

use Yiisoft\Profiler\ProfilerInterface;

trait ProfilerAwareTrait
{
    private ?ProfilerInterface $profiler = null;

    public function setProfiler(ProfilerInterface $profiler = null): void
    {
        $this->profiler = $profiler;
    }

    protected function getProfiler(): ?ProfilerInterface
    {
        return $this->profiler;
    }
}
