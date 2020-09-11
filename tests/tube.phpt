<?php

declare(strict_types=1);

/**
 * This file is a part of dakujem/cumulus package.
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */

namespace Dakujem\Cumulus\Test;

require_once __DIR__ . '/bootstrap.php';

use ArrayIterator;
use Dakujem\Cumulus\Pipeline;
use Tester\Assert;
use Tester\TestCase;
use TypeError;

/**
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _TubeTest extends TestCase
{
    public function testArrayTube()
    {
        $pipeline = [
            function (string $a) {
                return $a . 'foo';
            },
            function (string $a) {
                return $a . 'bar';
            },
        ];
        Assert::same('ifoobar', Pipeline::tube($pipeline)('i'));

        $pipeline = [
            function (string $a) {
                return $a . 'foo';
            },
        ];
        Assert::same('ifoo', Pipeline::tube($pipeline)('i'));
        Assert::same('foo', Pipeline::tube($pipeline)(''));
        Assert::error(function () use ($pipeline) {
            Pipeline::tube($pipeline)(); // passing null into string type hinted callable in $pipeline
        }, TypeError::class);
    }

    public function testIterableTube()
    {
        $pipeline = new ArrayIterator([
            function (string $a) {
                return $a . 'foo';
            },
            function (string $a) {
                return $a . 'bar';
            },
        ]);
        Assert::same('ifoobar', Pipeline::tube($pipeline)('i'));

        $pipeline = new ArrayIterator([
            function (string $a) {
                return $a . 'foo';
            },
        ]);
        Assert::same('ifoo', Pipeline::tube($pipeline)('i'));
        Assert::same('foo', Pipeline::tube($pipeline)(''));
        Assert::error(function () use ($pipeline) {
            Pipeline::tube($pipeline)(); // passing null into string type hinted callable in $pipeline
        }, TypeError::class);
    }

    public function testEmptyArrayTube()
    {
        Assert::same('i', Pipeline::tube([])('i'));
        Assert::same('', Pipeline::tube([])(''));
        Assert::same(42, Pipeline::tube([])(42));
        Assert::same(0, Pipeline::tube([])(0));
        Assert::same(null, Pipeline::tube([])(null));
    }
}

// run the test
(new _TubeTest)->run();
