<?php

declare(strict_types=1);

namespace Yiisoft\Db\Event;

/**
 * AfterSaveEvent represents the information available in {@see ActiveRecord::EVENT_AFTER_INSERT} and
 * {@see ActiveRecord::EVENT_AFTER_UPDATE}.
 */
class AfterSaveEvent
{
    /**
     * @var array The attribute values that had changed and were saved.
     */
    public $changedAttributes;
}
