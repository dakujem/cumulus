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


	public function testFoo()
	{

		$this->runCase('mysql://john:secret@localhost:3306/my_db', [
			'driver' => 'mysql',
			'port' => 3306,
			'host' => 'localhost',
			'username' => 'john',
			'password' => 'secret',
			'database' => 'my_db',
		]);

		$this->runCase('mysqli://localhost:3306/my_db', [
			'driver' => 'mysqli',
			'port' => 3306,
			'host' => 'localhost',
			'username' => NULL,
			'password' => NULL,
			'database' => 'my_db',
		]);

		$this->runCase('mysqli://localhost/foobar', [
			'driver' => 'mysqli',
			'port' => NULL,
			'host' => 'localhost',
			'username' => NULL,
			'password' => NULL,
			'database' => 'foobar',
		]);

		$this->runCase('test://192.168.3.5:1234', [
			'driver' => 'test',
			'port' => 1234,
			'host' => '192.168.3.5',
			'username' => NULL,
			'password' => NULL,
			'database' => NULL,
		]);

		$this->runCase('192.168.3.5:1234', [
			'driver' => NULL,
			'port' => 1234,
			'host' => '192.168.3.5',
			'username' => NULL,
			'password' => NULL,
			'database' => NULL,
		]);

		$this->runCase('localhost:3306', [
			'driver' => NULL,
			'port' => 3306,
			'host' => 'localhost',
			'username' => NULL,
			'password' => NULL,
			'database' => NULL,
		]);

		$this->runCase('mysql://localhost', [
			'driver' => 'mysql',
			'port' => NULL,
			'host' => 'localhost',
			'username' => NULL,
			'password' => NULL,
			'database' => NULL,
		]);

		$this->runCase(NULL, []);
		$this->runCase('', []);
	}


	//--------------------------------------------------------------------------
	//----------------------- Aux methods --------------------------------------

	private function runCase($url, array $expected)
	{
		$uc = new UrlConfig($url);

		// sanity test
		Assert::same($url, $uc->getUrl(), 'Getting the original URL');

		// test individual variables
		foreach (array_keys($expected) as $key) {
			Assert::same($expected[$key], $uc->get($key), 'Getting "' . $key . '" key');
		}

		// test getting the whole mapped config
		Assert::equal($expected, $uc->getConfig(), 'Getting complete configuration');

		// test PDO DSN
		$url && Assert::same("{$expected['driver']}: host={$expected['host']};dbname={$expected['database']}", $uc->getPdoDsn(), 'PDO DSN string');
		!$url && Assert::same(": host=;dbname=", $uc->getPdoDsn(), 'PDO DSN string if URL empty');
	}

}

// run the test
(new _UrlConfigTest)->run();


