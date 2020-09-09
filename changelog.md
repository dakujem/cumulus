
# Changelog

> 📖 back to [readme](readme.md)

Cumulus follows semantic versioning.


## v1.5

TODO 🚧
Added `Streamline` class.

Drop php 7.1 (to 7.4??)


## v1.4 ❕

Added `trim` to prevent adding `_` char instead of unintentional white space chars by [`parse_url`](https://www.php.net/manual/en/function.parse-url.php).
This potentially changes behaviour (albeit incorrect one), thus a new minor release and not a patch.


## v1.3

- dropped support for PHP 7.0


## v1.2

### v1.2.1

Backward compatibility fix:\
Any type of value is now accepted by `Dsn` as a URL, but will be ignored, instead of throwing a runtime exception.

### v1.2.0 ❕

Introduces [`Dsn`](src/Dsn.php) class.

**Changes**:
- the `UrlConfig` class has been replaced by [`Dsn`](src/Dsn.php) class and is now _deprecated_
    - you can keep using `UrlConfig`, it extends `Dsn` now
- added support for query parameters and fragment
- added option to pass custom mappings as argument to `Dsn` constructor
- added possibility to access configuration using _array access_ and _magic props_
- added value mapping helper

**Warning**\
Even though the public API has not changed, the internal structure _has_ changed in this release.\
In case you _extend_ `UrlConfig` class, you need to update your implementation.


## v1.1

- added [`LazyIterator`](src/LazyIterator.php)
- fixed bug in PDO string returned by `UrlConfig::getPdoDsn`


## v1.0

The initial release. Contains `UrlConfig` class.
