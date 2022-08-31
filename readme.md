# ☁ Cumulus

![PHP from Packagist](https://img.shields.io/packagist/php-v/dakujem/cumulus)
[![PHP Test](https://github.com/dakujem/cumulus/actions/workflows/php-test.yml/badge.svg)](https://github.com/dakujem/cumulus/actions/workflows/php-test.yml)
[![Coverage Status](https://coveralls.io/repos/github/dakujem/cumulus/badge.svg?branch=trunk)](https://coveralls.io/github/dakujem/cumulus?branch=trunk)

A set of plug-in utilities for easier development of cloud-enabled software.

> 💿 `composer require dakujem/cumulus`
>
> 📒 [Changelog](changelog.md)


## Documentation

Included classes:
- **`Pipeline`**
  - 👉 [Middleware and Pipelines](doc/pipeline.md)
  - a class for middleware and pipelines
- **`Dsn`**
  - 👉 [Data Source Name](doc/dsn.md)
  - a DSN configuration wrapper and parser
- **`LazyIterator`**
  - 👉 [LazyIterator](doc/lazyIterator.md)
  - an iterator for on-demand data provisioning

As of now, the library has no external dependencies.


## Compatibility

| `dakujem/cumulus` | PHP |
|---------|-----|
| `^2.0` | `^8` onwards |
| `^1.5`, `^1.6` | `7.2`, `7.3`, `7.4`, `8.0` |
| `^1.4` | `7.2`, `7.3`, `7.4` |


## Examples

**Pipelines & middleware**
```php
$foobarSuffixTube = Pipeline::tube([
    function (string $a): string {
        return $a . 'foo';
    },
    function (string $a): string {
        return $a . 'bar';
    },
]);
$foobarSuffixTube('iam'); // 'iamfoobar'

$foobarPrefixMiddleware = Pipeline::onion([
    function (string $a, callable $next): string {
        return $next('bar' . $a);
    },
    function (string $a, callable $next): string {
        return $next('foo' . $a);
    },
]);
$foobarPrefixMiddleware('youare'); // 'foobaryouare'
```
More in the documentation here: [Middleware and Pipelines](doc/pipeline.md).

**Dsn**
```php
$dsn = new Dsn('mysqli://john:secret@localhost/my_db');

// with optional default values
$driver = $dsn->get('driver', 'mysqli');
$port = $dsn->get('port', 3306);
// without optional defaults
$user = $dsn->get('username');
// using magic and array accessors:
$user = $dsn->username;
$user = $dsn['username'];
$pass = $dsn->password ?? '';
```
More in the documentation here: [Dsn (Data Source Name)](doc/dsn.md).


## Testing

Run unit tests using the following command:

`$` `composer test`


## Contributing

Ideas or contribution is welcome. Please send a PR or file an issue.

