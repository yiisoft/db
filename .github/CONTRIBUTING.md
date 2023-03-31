# Prerequisites

- [Yii goal and values](https://github.com/yiisoft/docs/blob/master/001-yii-values.md)
- [Namespaces](https://github.com/yiisoft/docs/blob/master/004-namespaces.md)
- [Git commit messages](https://github.com/yiisoft/docs/blob/master/006-git-commit-messages.md)
- [Exceptions](https://github.com/yiisoft/docs/blob/master/007-exceptions.md)
- [Interfaces](https://github.com/yiisoft/docs/blob/master/008-interfaces.md)

# Getting started

Since Yii 3 consists of many packages, we have a [special development tool](https://github.com/yiisoft/docs/blob/master/005-development-tool.md).

1. [Clone the repository](https://github.com/yiisoft/yii-dev-tool).

2. [Set up your own fork](https://github.com/yiisoft/yii-dev-tool#using-your-own-fork).

3. Now you are ready. Fork any package listed in `packages.php` and do `./yii-dev install username/package`.

4. Create a new branch for your changes

If you are only going to make a pull request in a single repository, simply create the new branch, where the name correctly identifies the fix or feature to be made.

if you are going to make a pull request in multiple repositories, create a **new branch with the same name in all repositories**, this allows our github actions workflow to sync all branches, and tests to run correctly.

5. [Check your changes](/docs/en/testing.md)

## Reporting issues

Please follow the guidelines below when creating an issue so that your issue can be more promptly resolved:

* Provide information including: the version of PHP, dbms and the type of operating system.
* Provide the **complete** error call stack if available. A screenshot to explain the issue is very welcome.
* Describe the steps for reproducing the issue. It would be even better if you could provide code to reproduce the issue.
* If possible you may even create a failing unit test and [send it as a pull request](#git-workflow).

If the issue is related to one of the dbms packages, please report it to the corresponding repository.

If you are unsure, [report it to the main repository](https://github.com/yiisoft/db/issues/new) (<https://github.com/yiisoft/db/issues>).

**Do not report an issue if**

* you are asking how to use some **Yii DB** feature. You should use [the forum](https://forum.yiiframework.com/c/yii-3-0/63) or [telegram](https://t.me/yii3en) for this purpose.
* your issue is about security. Please [contact us directly](https://www.yiiframework.com/security/) to report security issues.

**Avoid duplicated issues**

Before you report an issue, please search through [existing issues](https://github.com/yiisoft/db/issues) to see if your issue is already reported or fixed to make sure you are not reporting a duplicated issue.

If you don't have any particular package in mind to start with:

- [Check roadmap](https://github.com/yiisoft/docs/blob/master/003-roadmap.md).
- Check package issues at github. Usually there are some.
- Ask @samdark.
