<?php

declare(strict_types=1);

namespace Dakujem\Cumulus;

use ArrayAccess;
use LogicException;
use Stringable;
use Throwable;

/**
 * DSN-style configuration wrapper. Lazy and immutable.
 *
 * Useful for connection configurations that use URL DSNs but the app needs separate config fields or PDO DSN,
 * for example:
 * - databases on Heroku and other cloud service providers
 * - other remote service configurations that require a host, port, username, password, container name and possibly parameters
 *
 * "mysql://john:secret@localhost:3306/my_db?lazy=true&connections=10" => [
 *        username => john
 *        password => secret
 *        database => my_db
 *        port => 3306
 *        host => localhost
 *        driver => mysql
 *        params => [lazy:true, connections:10]
 *        pdo => "mysql:host=localhost;dbname=my_db"
 * ]
 *
 * Usage:
 *        $dsn = new Dsn("mysql://john:secret@localhost:3306/my_db");
 *        $dsn->getConfig();            // whole configuration array
 *        $dsn->host;                   // magic props
 *        $dsn['pdo'];                  // array access
 *        $dsn->get('port', 3306);      // method access, with optional default value
 *
 * Note: the immutability is related to the configuration, not to the class itself.
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
final class Dsn implements ArrayAccess, Stringable
{
    /**
     * Mapped configuration array, or a callable resolver that returns one.
     *
     * @var array|callable
     */
    private mixed $config;

    /**
     * Dsn.
     * Parses a URI into components using `parse_url` and maps them to a configuration optimized for setting up a service.
     *
     * Mappings are key-value _pairs_ from $mappings array. Each value can be either of:
     * - a string denoting a field from the parsed components
     * - a callable returning the desired value, signature fn(array $components, string $key): mixed
     *
     * The components available to the mappings are:
     * - scheme
     * - user
     * - pass
     * - host
     * - port
     * - path
     * - query (after ? )
     * - fragment (after # )
     *
     * @see parse_url()
     *
     * @param string|callable|null $uri a string uri or a callable that returns one (resolved at first access)
     * @param array|null $mappings custom config mappings, this will be MERGED with the default mappings
     */
    public function __construct(string|callable|null $uri = null, ?array $mappings = null)
    {
        $this->config = function () use ($uri, $mappings): array {
            $uriString = !is_string($uri) && is_callable($uri) ? ($uri)() : $uri;
            if ($uriString !== null && !is_string($uriString)) {
                $isProvider = !is_string($uri) && is_callable($uri);
                throw new LogicException(sprintf(
                    'The %s a string or null. Got %s.',
                    $isProvider ? 'URI provider must return' : 'URI is expected to be',
                    is_object($uriString) ? 'an instance of ' . get_class($uriString) : gettype($uriString),
                ));
            }
            if ($uriString === '' || $uriString === null) {
                return [];
            }
            $map = $mappings ? array_merge($this->getDefaultMappings(), $mappings) : $this->getDefaultMappings();
            $rawComponents = parse_url(trim($uriString)); // may be `false`
            if ($rawComponents === false) {
                throw new LogicException('Seriously malformed URI: ' . $uriString);
            }
            return self::mapComponents($rawComponents, $map);
        };
    }

    /**
     * Get the whole configuration array as-is.
     */
    public function getConfig(): array
    {
        if (!is_array($this->config)) {
            // resolve the configuration
            $this->config = ($this->config)();
        }
        return $this->config;
    }

    /**
     * Get corresponding configuration value for a given key.
     *
     * @param string $key
     * @param mixed $default default value to be used if the parameter does not exist or is `null`
     * @return mixed the return type is defined by the mapping of the configuration setting
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getConfig()[$key] ?? $default;
    }

    /**
     * Return the default mappings used to map URL components to configuration.
     */
    public function getDefaultMappings(): array
    {
        return [
            'driver' => 'scheme',
            'port' => 'port',
            'host' => 'host',
            'username' => 'user',
            'password' => 'pass',
            'database' => function (array $config): ?string {
                // remove preceding slash '/' from the path
                return ($config['path'] ?? null) !== null ? ltrim($config['path'], '/') : null;
            },
            'params' => function (array $config): ?array {
                // parse query string to native PHP types (recursively)
                return ($config['query'] ?? null) !== null ? static::queryToNativeTypes($config['query']) : null;
            },
            'fragment' => 'fragment',
            'pdo' => function (array $config): string {
                return
                    ($config['scheme'] ?? '') . ':host=' . ($config['host'] ?? '') .
                    ($config['port'] ?? null ? ';port=' . $config['port'] : '') .
                    ';dbname=' . ltrim($config['path'] ?? '', '/');
            },
        ];
    }

    /**
     * Runs the mapping from URL components to custom keys.
     *
     * Note:
     *        The null values are intentionally not filtered out.
     *        It could otherwise cause confusion if someone expected those keys to exist in array returned by getConfig.
     */
    private static function mapComponents(array $components, array $mappings): array
    {
        $res = [];
        foreach ($mappings as $name => $mapping) {
            $res[$name] = is_scalar($mapping) ? ($components[$mapping] ?? null) : $mapping($components, $name);
        }
        return $res;
    }

    /**
     * Returns a mapper that can be used to convert values to different values.
     *
     * Example: converting deprecated "mysql" adapter to "mysqli"
     * $dsn = new Dsn('mysql://localhost/my_db', [
     *        'driver' => Dsn::valueMapper(['mysql' => 'mysqli'], 'scheme'),
     * ]);
     * $dsn->driver  // "mysqli"
     */
    public static function valueMapper(array $valueMap, ?string $component = null): callable
    {
        return function (array $components, string $key) use ($component, $valueMap): mixed {
            // if $component was not provided, use the configuration $key
            $value = $components[$component ?? $key] ?? null;
            return $valueMap[$value] ?? $value;
        };
    }

    /**
     * Parse query encoded string to PHP native types (integer, double or boolean), arrays are parsed recursively.
     */
    public static function queryToNativeTypes(string $query): array
    {
        $params = [];
        parse_str($query, $params);
        // cast params to native types
        $foonc = function ($params) use (&$foonc) {
            array_walk($params, function (&$val) use ($foonc) {
                // integer or floating point
                if (is_numeric($val)) {
                    $val = $val + 0;
                }

                // boolean
                $low = is_string($val) ? strtolower($val) : null;
                if ($low === 'true') {
                    $val = true;
                } elseif ($low === 'false') {
                    $val = false;
                }

                // array (recursion)
                if (is_array($val)) {
                    $val = $foonc($val);
                }
            });
            return $params;
        };
        return $foonc($params);
    }

    /**
     * Casting the Dsn object to string results in a JSON-encoded string containing the configuration array.
     */
    public function __toString(): string
    {
        try {
            return json_encode($this->getConfig());
        } catch (Throwable) {
            return '';
        }
    }

    /**
     * Provide magic prop access.
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->get($offset) !== null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('It is not possible to mutate the configuration.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('It is not possible to mutate the configuration.');
    }
}
