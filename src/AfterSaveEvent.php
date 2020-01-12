<?php

namespace Yiisoft\Db;

use yii\base\Event;

/**
 * AfterSaveEvent represents the information available in {@see ActiveRecord::EVENT_AFTER_INSERT} and
 * {@see ActiveRecord::EVENT_AFTER_UPDATE}.
 */
class AfterSaveEvent extends Event
{
    /**
     * @var array The attribute values that had changed and were saved.
     */
    public $changedAttributes;
}
