Upgrading Instructions for Yii Framework Database 3.0
=====================================================

This file contains the upgrade notes for the database layer Yii 3.0.
These notes highlight changes that could break your application when you upgrade Yii from one version to another.
Even though we try to ensure backwards compatibility (BC) as much as possible, sometimes
it is not possible or very complicated to avoid it and still create a good solution to
a problem. While upgrade to Yii 3.0 might require substantial changes to both your application and extensions,
the changes are bearable and require "refactoring", not "rewrite".
All the "Yes, it is" cool stuff and Yii soul are still in place.

Changes summary:
* `Yiisoft\Db\Connection::$charset` has been removed. All support PDO classes allow you to specify the connection charset in the DSN.