<?php

namespace Yiisoft\Db\Events;

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
