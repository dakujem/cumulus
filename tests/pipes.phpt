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
use IteratorAggregate;
use Tester\Assert;
use Tester\TestCase;

abstract class _PipelineHelper implements IteratorAggregate
{
//    protected array $stages;
    protected $stages;

    public function __construct(iterable $initialPipeline = [])
    {
        $this->stages = is_array($initialPipeline) ? $initialPipeline : iterator_to_array($initialPipeline);
    }

    public function pipe(callable $pipe)
    {
        $this->stages[] = $pipe;
        return $this;
    }

    abstract public function execute($passable = null);

    public function getIterator()
    {
        return new ArrayIterator($this->stages);
    }
}

final class _OnionPipelineHelper extends _PipelineHelper
{
    public function execute($passable = null)
    {
        return Pipeline::onion($this)($passable);
    }
}

final class _TubePipelineHelper extends _PipelineHelper
{
    public function execute($passable = null)
    {
        return Pipeline::tube($this)($passable);
    }
}

/**
 * @author Andrej Rypak (dakujem) <xrypak@gmail.com>
 */
class _PipesHelperTest extends TestCase
{
    public function testOnion()
    {
        $pipeline =
            (new _OnionPipelineHelper())
                ->pipe(function (string $passable, callable $next) {
                    return $next($passable . '.');
                })
                ->pipe(function (array $passable, callable $next) {
                    return $next(implode(' ', $passable));
                })
                ->pipe(function (array $passable, callable $next) {
                    return $next(array_merge($passable, ['Hello', 'world']));
                });
        Assert::same('Hello world.', $pipeline->execute([]));
    }

    public function testTube()
    {
        $pipeline = (new _TubePipelineHelper())
            ->pipe(function (string $a) {
                return $a . 'foo';
            })
            ->pipe(function (string $a) {
                return $a . 'bar';
            });
        Assert::same('ifoobar', $pipeline->execute('i'));
    }
}

// run the test
(new _PipesHelperTest)->run();
