<?php

declare(strict_types=1);

/**
 * This file is a part of dakujem/cumulus package.
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */

namespace Dakujem\Cumulus\Test;

require_once __DIR__ . '/bootstrap.php';

use ArrayIterator;
use Dakujem\Cumulus\Dsn;
use Dakujem\Cumulus\LazyIterator;
use LogicException;
use Tester\Assert;
use Tester\TestCase;


/**
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _LayIteratorTest extends TestCase
{
    private $set1 = [
        'a' => 'A',
        'b' => 'B',
        'c' => 'C',
        'd' => 'D',
    ];

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


    public function testBasics()
    {
        $it0 = new LazyIterator(function () {
            return [];
        });
        $it00 = new LazyIterator(function () {
            return new ArrayIterator([]);
        });
        $it1 = new LazyIterator(function () {
            return $this->set1;
        });
        Assert::same([], iterator_to_array($it0), 'empty result');
        Assert::same([], iterator_to_array($it00), 'empty result (via iterator)');
        Assert::same($this->set1, iterator_to_array($it1), 'basic result');

        // exceptions (wrong types)
        Assert::exception(function () {
            $it = new LazyIterator(function () {
                return 'foo';
            });
            iterator_to_array($it);
        }, LogicException::class, 'The provider callable must return an iterable type, string returned.');
        Assert::exception(function () {
            $it = new LazyIterator(function () {
                return 0;
            });
            iterator_to_array($it);
        }, LogicException::class, 'The provider callable must return an iterable type, integer returned.');
        Assert::exception(function () {
            $it = new LazyIterator(function () {
                return new Dsn();
            });
            iterator_to_array($it);
        }, LogicException::class, 'The provider callable must return an iterable type, an instance of Dakujem\Cumulus\Dsn returned.');
    }


    //--------------------------------------------------------------------------
    //----------------------- Aux methods --------------------------------------
}

// run the test
(new _LayIteratorTest)->run();
