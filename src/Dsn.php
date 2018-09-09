<?php


namespace Dakujem\Cumulus;

use ArrayAccess,
	Exception,
	LogicException;


/**
 * DSN-style configuration wrapper. Lazy and immutable.
 *
 * Useful for connection configurations that use URL DSNs but the app needs separate config fields or PDO DSN,
 * for example:
 * - databases on Heroku and other cloud service providers
 * - other remote service configurations that require a host, port, username, password, container name and possibly parameters
 *
 * "mysql://john:secret@localhost:3306/my_db?lazy=true&connections=10" => [
 * 		username => john
 * 		password => secret
 * 		database => my_db
 * 		port => 3306
 * 		host => localhost
 * 		driver => mysql
 * 		params => [lazy:true, connections:10]
 * 		pdo => "mysql:host=localhost;dbname=my_db"
 * ]
 *
 * Usage:
 * 		$dsn = new Dsn("mysql://john:secret@localhost:3306/my_db");
 * 		$dsn->getConfig();			// whole configuration array
 * 		$dsn->host;					// magic props
 * 		$dsn['pdo'];				// array access
 * 		$dsn->get('port', 3306);	// method access, with optional default value
 *
 * Note: the immutability is related to the configuration, not to the class itself.
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class Dsn implements ArrayAccess
{

	/**
	 * The configuration URL.
	 *
	 * @var string
	 */
	protected $url = null;

	/**
	 * Mapped configuration array.
	 *
	 * @var array
	 */
	protected $config = [];

	/**
	 * Array of mappings.
	 * URL components can be used (see parse_url documentation for more info):
	 * - scheme
	 * - user
	 * - pass
	 * - host
	 * - port
	 * - path
	 * - query (after ? )
	 * - fragment (after # )
	 *
	 * @var array in format: [ desiredKey => mapper, ] where mapper is either string name of a component or callable returning a string
	 */
	protected $mappings = [];

	/**
	 * URL compnents, the result of parse_url call.
	 *
	 * @var array
	 */
	protected $int = [];


	/**
	 * Dsn.
	 * Parses a URL into components and maps them to a configuration optimized for setting up a service.
	 *
	 * @param string|callable|null $url passing a callable will call it on-demand, the return value should be a string.
	 * @param array $mappings custom config mappings, this will be merged with the default mappings
	 */
	public function __construct($url = null, array $mappings = null)
	{
		$this->url = $url;
		// custom mappings will override the default mappings
		$this->mappings = $mappings ? array_merge($this->getDefaultMappings(), $mappings) : $this->getDefaultMappings();
	}


	/**
	 * Get the whole configuration array as-is.
	 *
	 * @return array
	 */
	public function getConfig(): array
	{
		$url = $this->getUrl();
		if ($this->config === [] && $url !== null && $url !== '') {
			$this->int = parse_url($url);
			$this->config = $this->map($this->int, $this->mappings);
		}
		return $this->config;
	}


	/**
	 * Get corresponding configuration value for a given key.
	 *
	 * @param string $key
	 * @param mixed $default default value to be used if the parameter does not exist or is null
	 * @return mixed the return type is defined by the type of the configuration setting
	 */
	public function get($key, $default = null)
	{
		return $this->getConfig()[$key] ?? $default;
	}


	/**
	 * Return the original URL passed in constructor.
	 * If callable type was passed, return the result.
	 *
	 * @return string|null
	 */
	public function getUrl() //:?string
	{
		if ($this->url !== null && !is_string($this->url) && is_callable($this->url)) {
			$this->url = call_user_func($this->url);
		}
		if ($this->url !== null && !is_string($this->url)) {
			throw new LogicException(sprintf('An invalid URL of type %s has been provided.', is_object($this->url) ? get_class($this->url) : gettype($this->url)));
		}
		return $this->url;
	}


	/**
	 * Return the default mappings used to map URL components to configuration.
	 *
	 * @return array
	 */
	public function getDefaultMappings(): array
	{
		return [
			'driver' => 'scheme',
			'port' => 'port',
			'host' => 'host',
			'username' => 'user',
			'password' => 'pass',
			'database' => function($config) {
				// remove preceeding slash '/' from the path
				return ($config['path'] ?? null) !== null ? ltrim($config['path'], '/') : null;
			},
			'params' => function($config) {
				// parse query string to native PHP types (recursively)
				return ($config['query'] ?? null) !== null ? static::queryToNativeTypes($config['query']) : null;
			},
			'fragment' => 'fragment',
			'pdo' => function($config) {
				return ($config['scheme'] ?? '') . ':host=' . ( $config['host'] ?? '') . ';dbname=' . ltrim($config['path'] ?? '', '/');
			},
		];
	}


	/**
	 * Runs the mapping from URL components to custom keys.
	 *
	 * Note:
	 * 		The null values are intentionally not filtered out.
	 * 		It could otherwise cause confusion if someone expected those keys to exist in array returned by getConfig.
	 *
	 * @param array $components
	 * @param array $mappings
	 * @return array
	 */
	protected function map(array $components, array $mappings): array
	{
		$res = [];
		foreach ($mappings as $name => $mapping) {
			$res[$name] = is_scalar($mapping) ? ($components[$mapping] ?? null) : call_user_func($mapping, $components, $name);
		}
		return $res;
	}


	public static function valueMapper(array $valueMap): callable
	{
		return function($value) use ($valueMap) {
			return $valueMap[$value] ?? $value;
		};
	}


	/**
	 * Parse query encoded string to PHP native types (integer, double or boolean), arrays are parsed recursively.
	 *
	 * @param string $query
	 * @return array
	 */
	public static function queryToNativeTypes(string $query): array
	{
		$params = [];
		parse_str($query, $params);
		// cast params to native types
		$foonc = function($params) use (&$foonc) {
			array_walk($params, function(&$val) use ($foonc) {

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
					$val = call_user_func($foonc, $val);
				}
			});
			return $params;
		};
		return call_user_func($foonc, $params);
	}


	public function __toString()
	{
		try {
			return json_encode($this->getConfig());
		} catch (Exception $e) {
			return '';
		}
	}


	/**
	 * Provide magic prop access.
	 */
	public function __get($name)
	{
		return $this->get($name);
	}


	public function offsetGet($offset)
	{
		return $this->get($offset);
	}


	public function offsetExists($offset): bool
	{
		return $this->get($offset) !== null;
	}


	public function offsetSet($offset, $value): void
	{
		throw new LogicException('It is not possible to mutate the configuration.');
	}


	public function offsetUnset($offset): void
	{
		throw new LogicException('It is not possible to mutate the configuration.');
	}

}
