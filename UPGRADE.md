# Upgrading Instructions for Yii Database

This file contains the upgrade notes for the Yii Database.
These notes highlight changes that could break your application when you upgrade it from one version to another.
Even though we try to ensure backwards compatibility (BC) as much as possible, sometimes
it isn't possible or very complicated to avoid it and still create a good solution to
a problem. While upgrade to Yii 3.0 might require substantial changes to both your application and extensions,
the changes are bearable and require "refactoring", not "rewrite".
All the "Yes, it is" cool stuff, and Yii soul is still in place.

Changes summary:

* `Yiisoft\Db\Connection::$charset` has been removed. All supported PDO classes allow you to specify the connection
  charset in the DSN.
