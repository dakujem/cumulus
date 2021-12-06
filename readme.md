# ☁ Cumulus

![PHP from Packagist](https://img.shields.io/packagist/php-v/dakujem/cumulus)
![PHP 8 ready & tested](https://img.shields.io/static/v1?label=php%208&message=tested%20%F0%9F%91%8D&color=green)
[![PHP Test](https://github.com/dakujem/cumulus/actions/workflows/php-test.yml/badge.svg)](https://github.com/dakujem/cumulus/actions/workflows/php-test.yml)

A set of plug-in utilities for easier development of cloud-enabled software.

> 💿 `composer require dakujem/cumulus`
>
> 📒 [Changelog](changelog.md)


## Documentation

Included classes:
- [`Pipeline`](doc/pipeline.md)
	- a class for middleware and pipelines
- [`Dsn`](doc/dsn.md)
	- a DSN configuration wrapper and parser
- [`LazyIterator`](doc/lazyIterator.md)
	- an iterator for on-demand data provisioning

As of now, the library has no external dependencies.


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

