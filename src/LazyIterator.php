<?php

declare(strict_types=1);

namespace Dakujem\Cumulus;

use ArrayIterator;
use Iterator;
use IteratorIterator;
use LogicException;
use OuterIterator;
use Traversable;

/**
 * LazyIterator & Mapper.
 *
 * Lazily iterate over a set provided by a callable,
 * optionally applying one or more mapper functions on each of the elements.
 *
 * Note:    the provider callable is only called upon iteration, thus "lazy" iterator.
 *            The iteration over the set provided by the provider is not lazy
 *            (unless a traversable with lazy loading of elements is returned).
 *
 * @author Andrej Rypák (dakujem) <xrypak@gmail.com>
 */
class LazyIterator implements OuterIterator
{
    /**
     * A function that provides data.
     * The function gets called in the moment of iteration.
     *
     * The function signature is:
     * function(void): iterable
     *
     * @var callable
     */
    private $provider;

    /**
     * The inner iterator is created from the result returned by the provider.
     *
     * @var Iterator
     */
    private $iterator = null;

    /**
     * An array of item mappers that act as a pipeline.
     * For each element in the set, every mapper is called in a "pipe" - the result of each mapper is passed to the next mapper as an argument.
     *
     * Each mapper is a callable with signature:
     * function(mixed $element): mixed
     *
     * @var callable[]
     */
    private $pipeline = [];

    public function __construct(callable $provider, callable $mapper = null)
    {
        $this->provider = $provider;
        $mapper !== null && $this->addMapper($mapper);
    }

    public function addMapper(callable $callable)
    {
        $this->pipeline[] = $callable;
        return $this;
    }

    public function getInnerIterator(): Iterator
    {
        if ($this->iterator === null) {
            $res = ($this->provider)();
            if (is_array($res)) {
                $this->iterator = new ArrayIterator($res);
            } elseif ($res instanceof Traversable) {
                $this->iterator = new IteratorIterator($res);
            } else {
                throw new LogicException(sprintf('The provider callable must return an iterable type, %s returned.', is_object($res) ? 'an instance of ' . get_class($res) : gettype($res)));
            }
        }
        return $this->iterator;
    }

    public function current()
    {
        $current = $this->getInnerIterator()->current();
        foreach ($this->pipeline as $mapper) {
            $current = $mapper($current, $this->key());
        }
        return $current;
    }

    public function key()
    {
        return $this->getInnerIterator()->key();
    }

    public function next(): void
    {
        $this->getInnerIterator()->next();
    }

    public function rewind(): void
    {
        $this->getInnerIterator()->rewind();
    }

    public function valid(): bool
    {
        return $this->getInnerIterator()->valid();
    }
}
