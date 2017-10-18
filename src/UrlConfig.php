<?php


namespace Dakujem\Cumulus;


/**
 * UrlConfig
 *
 * Useful for connection configurations that use URL DSNs but the app needs separate config fields or PDO DSN,
 * for example:
 * - JawsDB (mySQL or MariaDB) on Heroku
 *
 * "mysql://john:secret@localhost:3306/my_db" => [
 * 		username => john
 * 		password => secret
 * 		database => my_db
 * 		port => 3306
 * 		host => localhost
 * 		driver => mysql
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
	private $url = NULL;
	protected $mappings = [];
	private $config = [];
	private $int = [];


	public function __construct($url = NULL)
	{
		$this->url = $url;

		if ($this->mappings === []) {
			$this->mappings = [
				'driver' => 'scheme',
				'port' => 'port',
				'host' => 'host',
				'username' => 'user',
				'password' => 'pass',
				'database' => function($config) {
					$db = $config['path'] ?? NULL;
					return $db === NULL ? NULL : ltrim($db, '/');
				},
			];
		}
	}


	protected function map(array $config, array $mappings): array
	{
		$res = [];
		foreach ($mappings as $name => $mapping) {
			$res[$name] = is_scalar($mapping) ? ($config[$mapping] ?? NULL) : call_user_func($mapping, $config, $name);
		}
		return $res;
	}


	public function getConfig()
	{
		$url = $this->getUrl();
		if ($this->config === [] && $url !== NULL && $url !== '') {
			$this->int = parse_url($url);
			$this->config = $this->map($this->int, $this->mappings);
		}
		return $this->config;
	}


	public function get($what, $default = NULL)
	{
		return $this->getConfig()[$what] ?? $default;
	}


	public function getUrl()
	{
		return $this->url;
	}


	public function getPdoDsn()
	{
		// "mysql:host=localhost;dbname=my_db"
		return ($this->int['scheme'] ?? '') . ': host=' . ( $this->int['host'] ?? '') . ';dbname=' . ltrim($this->int['path'] ?? '', '/');
	}

}
