<?php


namespace Dakujem\Cumulus;


/**
 * UrlConfig
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
 *
 * @todo allow getPort, getUsername... magic method calls
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class UrlConfig
{

	/**
	 * The configuration URL.
	 *
	 * @var string
	 */
	private $url = null;

	/**
	 * Mapped configuration array.
	 *
	 * @var array
	 */
	private $config = [];

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
	private $int = [];


	public function __construct($url = null, array $mappings = null)
	{
		$this->url = $url;
		$this->mappings = $mappings ? array_merge($this->getDefaultMappings(), $mappings) : $this->getDefaultMappings();
	}


	public function getConfig()
	{
		$url = $this->getUrl();
		if ($this->config === [] && $url !== null && $url !== '') {
			$this->int = parse_url($url);
			$this->config = $this->map($this->int, $this->mappings);
		}
		return $this->config;
	}


	public function get($what, $default = null)
	{
		return $this->getConfig()[$what] ?? $default;
	}


	public function getUrl()
	{
		return $this->url;
	}


	/**
	 * Return a PDO string in format:
	 * "mysql:host=localhost;dbname=my_db"
	 *
	 * Note: This is an alias to calling $conf->get('pdo', '')
	 *
	 * @return string
	 */
	public function getPdoDsn(): string
	{
		return $this->get('pdo', '');
	}


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

}
