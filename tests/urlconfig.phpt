<?php

/**
 * This file is a part of dakujem/cumulus package.
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */


namespace Dakujem\Cumulus\Test;

require_once __DIR__ . '/bootstrap.php';

use Dakujem\Cumulus\UrlConfig,
	Tester\Assert,
	Tester\TestCase;


/**
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _UrlConfigTest extends TestCase
{


	protected function setUp()
	{
		parent::setUp();
	}


	protected function tearDown()
	{
		parent::tearDown();
	}


	//--------------------------------------------------------------------------
	//----------------------- Test methods -------------------------------------


	public function testUrlParsing()
	{

		$this->runCase('mysql://john:secret@localhost:3306/my_db', [
			'driver' => 'mysql',
			'port' => 3306,
			'host' => 'localhost',
			'username' => 'john',
			'password' => 'secret',
			'database' => 'my_db',
			'params' => null,
			'fragment' => null,
		]);

		$this->runCase('mysqli://localhost:3306/my_db', [
			'driver' => 'mysqli',
			'port' => 3306,
			'host' => 'localhost',
			'username' => null,
			'password' => null,
			'database' => 'my_db',
			'params' => null,
			'fragment' => null,
		]);

		$this->runCase('mysql://john:secret@localhost:3306/my_db?foo=bar&empty=&lazy=true&eager=false&zero=0&integer=42&float=3.14159#some-fragment/another#13', [
			'driver' => 'mysql',
			'port' => 3306,
			'host' => 'localhost',
			'username' => 'john',
			'password' => 'secret',
			'database' => 'my_db',
			'params' => [
				'foo' => 'bar',
				'empty' => '',
				'lazy' => true,
				'eager' => false,
				'zero' => 0,
				'integer' => 42,
				'float' => 3.14159,
			],
			'fragment' => 'some-fragment/another#13',
		]);

		$this->runCase('mysql://localhost?arr[42]=42&arr[hello]=world&arr[a][b][c]=deep+recursion', [
			'driver' => 'mysql',
			'port' => null,
			'host' => 'localhost',
			'username' => null,
			'password' => null,
			'database' => null,
			'params' => [
				'arr' => [
					'42' => 42,
					'hello' => 'world',
					'a' => ['b' => ['c' => 'deep recursion']],
				],
			],
			'fragment' => null,
		]);

		$this->runCase('mysqli://localhost/foobar', [
			'driver' => 'mysqli',
			'port' => null,
			'host' => 'localhost',
			'username' => null,
			'password' => null,
			'database' => 'foobar',
			'params' => null,
			'fragment' => null,
		]);
		$this->runCase('mysqli://localhost#foobar', [
			'driver' => 'mysqli',
			'port' => null,
			'host' => 'localhost',
			'username' => null,
			'password' => null,
			'database' => null,
			'params' => null,
			'fragment' => 'foobar',
		]);
		$this->runCase('mysqli://localhost?foobar', [
			'driver' => 'mysqli',
			'port' => null,
			'host' => 'localhost',
			'username' => null,
			'password' => null,
			'database' => null,
			'params' => ['foobar' => ''],
			'fragment' => null,
		]);

		$this->runCase('test://192.168.3.5:1234', [
			'driver' => 'test',
			'port' => 1234,
			'host' => '192.168.3.5',
			'username' => null,
			'password' => null,
			'database' => null,
			'params' => null,
			'fragment' => null,
		]);

		$this->runCase('192.168.3.5:1234', [
			'driver' => null,
			'port' => 1234,
			'host' => '192.168.3.5',
			'username' => null,
			'password' => null,
			'database' => null,
			'params' => null,
			'fragment' => null,
		]);

		$this->runCase('localhost:3306', [
			'driver' => null,
			'port' => 3306,
			'host' => 'localhost',
			'username' => null,
			'password' => null,
			'database' => null,
			'params' => null,
			'fragment' => null,
		]);

		$this->runCase('mysql://localhost', [
			'driver' => 'mysql',
			'port' => null,
			'host' => 'localhost',
			'username' => null,
			'password' => null,
			'database' => null,
			'params' => null,
			'fragment' => null,
		]);

		$this->runCase(null, []);
		$this->runCase('', []);
	}

	//--------------------------------------------------------------------------
	//----------------------- Aux methods --------------------------------------


	/**
	 * Note:
	 * 		The order of members in the $expected array matters!
	 *
	 * @param string $url
	 * @param array $expected
	 * @param bool $fullTest
	 */
	private function runCase($url, array $expected, bool $fullTest = true)
	{
		$uc = new UrlConfig($url);

		// sanity test
		Assert::same($url, $uc->getUrl(), 'Getting the original URL');

		// test getting individual variables
		foreach (array_keys($expected) as $key) {
			Assert::same($expected[$key], $uc->get($key), "Getting \"$key\" key from $url");
		}

		// expected PDO
		$pdo = $url ? "{$expected['driver']}:host={$expected['host']};dbname={$expected['database']}" : '';

		if ($fullTest) {
			// test getting the whole mapped config
			if ($pdo !== '') {
				// Note: the full config also contains the PDO string
				$expected['pdo'] = $pdo;
			}
			Assert::equal($expected, $uc->getConfig(), "Getting complete configuration from $url");
		}

		// test PDO DSN
		Assert::same($pdo, $uc->getPdoDsn(), 'PDO DSN string');
	}

}

// run the test
(new _UrlConfigTest)->run();


