# Contributing

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


## Git workflow

So you want to contribute to **Yii DB**? Great! But to increase the chances of your changes being accepted quickly, please follow the following steps.

If you are new to Git and GitHub, you might want to first check out [GitHub help](https://help.github.com/), [try Git](https://try.github.com) or learn something about [Git internal data model](https://nfarina.com/post/9868516270/git-is-simpler).

### Prepare your development environment

The following steps will create pull requests.

1. [Fork](https://help.github.com/fork-a-repo/) the **Yii DB** repository on GitHub and clone your fork to your development environment

```
git clone git@github.com:yiisoft/db.git
```

If you have trouble setting up Git with GitHub in Linux, or are getting errors like "Permission Denied (publickey)", then you must [setup your Git installation to work with GitHub](https://help.github.com/linux-set-up-git/)

> Tip: if you're not fluent with Git, we recommend reading excellent free [Pro Git book](https://git-scm.com/book/en/v2).

2. Add the main **Yii DB** repository as an additional git remote called "upstream"

Change to the directory where you cloned **Yii DB**, normally, "db". Then enter the following command:

```
git remote add upstream https://github.com/yiisoft/db.git
```

3. Create a new branch for your changes

If you are only going to make a pull request in a single repository, simply create the new branch, where the name correctly identifies the fix or feature to be made.

if you are going to make a pull request in multiple repositories, create a **new branch with the same name in all repositories**, this allows our github actions workflow to sync all branches, and tests to run correctly.


4. [Check your changes](/docs/en/testing.md)
