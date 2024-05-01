# Internals

- [Testing](./testing.md)

## Static analysis

The code is statically analyzed with [Psalm](https://psalm.dev/). To run static analysis:

```shell
./vendor/bin/psalm
```

## Code style

Use [Rector](https://github.com/rectorphp/rector) to make codebase follow some specific rules or
use either newest or any specific version of PHP:

```shell
./vendor/bin/rector
```

## Dependencies

This package uses [composer-require-checker](https://github.com/maglnet/ComposerRequireChecker) to check if all
dependencies are correctly defined in `composer.json`. To run the checker, execute the following command:

```shell
./vendor/bin/composer-require-checker
```
