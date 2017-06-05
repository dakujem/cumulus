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
 * 		adapter => mysql
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
				'scheme' => 'adapter',
				'port' => 'port',
				'host' => 'host',
				'user' => 'username',
				'pass' => 'password',
				'path' => function($val) {
					return ['database', ltrim($val, '/')];
				},
			];
		}
	}


	protected function map(array $config): array
	{
		$res = [];
		foreach ($this->mappings as $srcKey => $mapping) {
			$val = $config[$srcKey] ?? NULL;
			if (is_string($mapping)) {
				$dstKey = $mapping;
			} else {
				list($dstKey, $val) = call_user_func($mapping, $val, $srcKey, $config);
			}
			$res[$dstKey] = $val;
		}
		return $res;
	}


	public function get($what, $default = NULL)
	{
		if ($this->config === [] && $this->getUrl() !== NULL) {
			$this->int = parse_url($this->getUrl());
			$this->config = $this->map($this->int);
		}
		return $this->config[$what] ?? $default;
	}


	public function getUrl()
	{
		return $this->url;
	}


	public function getPdoDsn()
	{
		// "mysql:host=localhost;dbname=my_db"
		return ($this->int['adapter'] ?? '') . ':host=' . ( $this->int['host'] ?? '') . ';dbname=' . ltrim($this->int['path'] ?? '', '/');
	}

}
