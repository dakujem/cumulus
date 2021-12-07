<?php

declare(strict_types=1);

/**
 * This file is a part of dakujem/cumulus package.
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */

namespace Dakujem\Cumulus\Test;

require_once __DIR__ . '/bootstrap.php';

use Dakujem\Cumulus\Dsn;
use LogicException;
use RuntimeException;
use Tester\Assert;
use Tester\TestCase;
use TypeError;

/**
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _DsnTest extends TestCase
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

        $this->runCase("  mysqli://localhost/foobar\n  ", [
            'driver' => 'mysqli',
            'port' => null,
            'host' => 'localhost',
            'username' => null,
            'password' => null,
            'database' => 'foobar',
            'params' => null,
            'fragment' => null,
        ]);

        $this->runCase(null, []);
        $this->runCase(fn() => null, []);
        $this->runCase('', []);
    }

    /** @noinspection PhpStrictTypeCheckingInspection */
    public function testTypeErrors()
    {
        Assert::throws(fn() => new Dsn(42), TypeError::class);
        Assert::throws(fn() => new Dsn(true), TypeError::class);
        Assert::throws(fn() => new Dsn(false), TypeError::class);
        Assert::throws(fn() => new Dsn([]), TypeError::class);
        Assert::throws(fn() => new Dsn((object)[]), TypeError::class);
    }

    public function testProvider()
    {
        $this->individual(fn() => 'foo', ['database' => 'foo']);
        Assert::same([], (new Dsn(fn() => null))->getConfig());
        $this->individual(fn() => null, []);
        $this->individual(fn() => 'mysql://john:secret@localhost:3306/my_db', [
            'database' => 'my_db',
            'host' => 'localhost',
            'port' => 3306,
            'username' => 'john',
            'password' => 'secret',
            'driver' => 'mysql',
        ]);
    }

    public function testProviderError()
    {
        Assert::throws(
            fn() => (new Dsn(fn() => 42))->getConfig(),
            LogicException::class,
            'The URI provider must return a string or null. Got integer.'
        );
        Assert::throws(
            fn() => (new Dsn(fn() => new RuntimeException()))->getConfig(),
            LogicException::class,
            'The URI provider must return a string or null. Got an instance of RuntimeException.'
        );
    }

    public function testEdgeCases()
    {
        Assert::throws(fn() => (new Dsn('/foo:2019'))->getConfig(), LogicException::class);

        $this->runCase('', []);

        $this->individual('42', ['database' => '42']);
        $this->individual('-42', ['database' => '-42']);
        $this->individual('-', ['database' => '-']);
        $this->individual('~', ['database' => '~']);
        $this->individual('/', ['database' => '']); // this is interesting
        $this->individual('/foo', ['database' => 'foo']);
    }

    public function testUsage()
    {
        // test multiple ways to obtain config values
        $dsn = new Dsn('mysql://john:secret@localhost:3306/my_db');
        Assert::same('localhost', $dsn->get('host'), "Getter method access");
        Assert::same('localhost', $dsn->host, "Magic props");
        Assert::same('localhost', $dsn['host'], "Array access");
    }

    public function testToStringWithPort()
    {
        // test multiple ways to obtain config values
        $dsn = new Dsn('mysql://john:secret@localhost:3306/my_db');
        // test __toString
        Assert::same('{"driver":"mysql","port":3306,"host":"localhost","username":"john","password":"secret","database":"my_db","params":null,"fragment":null,"pdo":"mysql:host=localhost;port=3306;dbname=my_db"}', (string)$dsn, "Convert to string");
    }

    public function testToStringWithoutPort()
    {
        // test multiple ways to obtain config values
        $dsn = new Dsn('mysql://john:secret@localhost/my_db');
        // test __toString
        Assert::same('{"driver":"mysql","port":null,"host":"localhost","username":"john","password":"secret","database":"my_db","params":null,"fragment":null,"pdo":"mysql:host=localhost;dbname=my_db"}', (string)$dsn, "Convert to string");
    }

    public function testValueMapping()
    {
        // this is a test for a useful case when, for example, one wants to map from "mysql" to "mysqli" driver without changing the DSN URL
        $dsn = new Dsn('mysql://john:secret@localhost:3306/my_db', [
            'driver' => Dsn::valueMapper(['mysql' => 'mysqli'], 'scheme'), // mapping scheme
            'port' => Dsn::valueMapper([3306 => 1234]), // mapping port
        ]);
        Assert::same('mysqli', $dsn->driver, 'The driver got converted from "mysql" to "mysqli"');
        Assert::same(1234, $dsn->port, 'The port got converted from "3306" to "1234"');
    }

    //--------------------------------------------------------------------------
    //----------------------- Aux methods --------------------------------------


    /**
     * Note:
     *        The order of members in the $expected array matters!
     *
     * @param string|callable|null $url
     * @param array $expected
     * @param bool $internalNull check for null being returned by $dsn->getUrl()
     */
    private function runCase($url, array $expected, bool $internalNull = false)
    {
        $dsn = new Dsn($url);
        if (!is_string($url) && is_callable($url)) {
            $url = $url();
        }

        // Test getting individual variables
        foreach (array_keys($expected) as $key) {
            Assert::same($expected[$key], $dsn->get($key), "The \"$key\" key from $url");
        }

        // Expected PDO
        $portPart = $expected['port'] ?? null ? "port={$expected['port']};" : '';
        $pdo = $url && !$internalNull ? "{$expected['driver']}:host={$expected['host']};{$portPart}dbname={$expected['database']}" : '';

        // Test getting the whole mapped config
        if ($pdo !== '') {
            // Note: the full config also contains the PDO string
            $expected['pdo'] = $pdo;
        }
        Assert::equal($expected, $dsn->getConfig(), "Complete configuration from $url");

        // Test PDO DSN
        Assert::same($pdo, $dsn->get('pdo', ''), 'PDO DSN string');
    }

    /**
     * Test individual components of the config array.
     * All the rest components must be `null` or not be present at all.
     */
    private function individual(mixed $input, array $expectedValues): void
    {
        $config = (new Dsn($input))->getConfig();
        foreach ($expectedValues as $componentName => $expectedValue) {
            Assert::same($expectedValue, $config[$componentName], "Component '$componentName'");
        }
        $allComponents = ['driver', 'port', 'host', 'username', 'password', 'database', 'params', 'fragment',];
        foreach (array_diff($allComponents, array_keys($expectedValues)) as $name) {
            Assert::null($config[$name] ?? null, "Component '$name'");
        }
    }
}

// run the test
(new _DsnTest)->run();
