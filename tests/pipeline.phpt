<?php

declare(strict_types=1);

/**
 * This file is a part of dakujem/cumulus package.
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */

namespace Dakujem\Cumulus\Test;

require_once __DIR__ . '/bootstrap.php';

use Dakujem\Cumulus\Pipeline;
use Tester\Assert;
use Tester\TestCase;
use TypeError;


/**
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _PipelineTest extends TestCase
{

    //--------------------------------------------------------------------------
    //----------------------- Test methods -------------------------------------


    public function testSampleTube()
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

    public function testEmptyTube()
    {
        Assert::same('i', Pipeline::tube([])('i'));
        Assert::same('', Pipeline::tube([])(''));
        Assert::same(42, Pipeline::tube([])(42));
        Assert::same(0, Pipeline::tube([])(0));
        Assert::same(null, Pipeline::tube([])(null));
    }

    public function testOnion()
    {
        // LIFO !
        $stages = [
            function (string $passable, callable $next) {
                return $next($passable . '.');
            },
            function (array $passable, callable $next) {
                return $next(implode(' ', $passable));
            },
            function (array $passable, callable $next) {
                return $next(array_merge($passable, ['Hello', 'world']));
            },
        ];
        Assert::same('Hello world.', Pipeline::onion($stages)([]));

        // Concatenate using a space character and add a dot character at the end:
        $concatenate = Pipeline::onion([
            function (string $passable, callable $next) {
                return $next($passable . '.');
            },
            function (array $passable, callable $next) {
                return $next(implode(' ', $passable));
            },
        ]);
        Assert::same('Hello ventil.', $concatenate(['Hello', 'ventil']));
        Assert::same('Hello.', $concatenate(['Hello']));
        Assert::same('.', $concatenate([]));
        Assert::error(function () use ($concatenate) {
            $concatenate(null);
        }, TypeError::class);
    }

    public function testOnionWithReversing()
    {
        $stages = [
            function (int $var, callable $next) {
                return $next($var * 2); // multiply value by 2
            },
            function (int $var, callable $next) {
                return $next($var + 3); // add 3
            },
        ];
        Assert::same((5 + 3) * 2, Pipeline::onion($stages)(5));
        Assert::same((5 * 2) + 3, Pipeline::onion(array_reverse($stages))(5));

        Assert::same((5 * 2) + 3, Pipeline::invertedOnion($stages)(5));
        Assert::same((5 + 3) * 2, Pipeline::invertedOnion(array_reverse($stages))(5));
    }

    public function testMiddleware()
    {
        $middleware = [
            function (int $val, callable $next): string {
                // this is the inner-most middleware and will be executed last
                return $next('The result is ' . ($val > 0 ? 'positive' : ($val < 0 ? 'negative' : 'zero')) . ': ' . $val);
            },
            function (int $val, callable $next): string {
                // skip the next middleware if the result is not even
                if ($val % 2 !== 0) {
                    return (string)$val;
                }
                return $next($val);
            },
            // multiply by 3
            function (int $val, callable $next): string {
                // example of before and after middleware:
                // - $val * 3 happens before the next middleware is called
                // - 'Message' prefixing happens after the next middleware returns
                return 'Message: ' . $next($val * 3);
            },
        ];
        $app = Pipeline::onion($middleware);
        Assert::same('Message: 15', $app(5));
        Assert::same('Message: The result is positive: 6', $app(2));
        Assert::same('Message: The result is zero: 0', $app(0));
        Assert::same('Message: -9', $app(-3));
        Assert::same('Message: The result is negative: -12', $app(-4));
    }

    public function testEmptyOnion()
    {
        Assert::same('i', Pipeline::onion([])('i'));
        Assert::same('', Pipeline::onion([])(''));
        Assert::same(42, Pipeline::onion([])(42));
        Assert::same(0, Pipeline::onion([])(0));
        Assert::same(null, Pipeline::onion([])(null));
    }




    //--------------------------------------------------------------------------
    //----------------------- Aux methods --------------------------------------
}

// run the test
(new _PipelineTest)->run();
