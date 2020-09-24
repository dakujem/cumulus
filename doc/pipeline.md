# Middleware and Pipelines

> 📖 back to [readme](../readme.md)

> Since `v1.5`

Using the [`Pipeline`](/Pipeline.php) class,
it is easy to create execution pipelines and middleware dispatchers.


## Tubes

"Tubes" are trivial pipelines, where every callable in a pipeline is always executed,
and the return value is passed from one callable to the input of the following one
until the end is reached.

```
Pipeline diagram for [Stage1, Stage2, Stage3]:

             +--------+     +--------+     +--------+
  input  --> | Stage1 | --> | Stage2 | --> | Stage3 | --> result
             +--------+     +--------+     +--------+
```

```php
$foobarSuffix = Pipeline::tube([
    function (string $a): string {
        return $a . 'foo';
    },
    function (string $a): string {
        return $a . 'bar';
    },
]);

$foobarSuffix('iam'); // 'iamfoobar'
```

Even though implementing a "tube" is as simple as typing a single foreach,
this helper method may be useful... and looks kůler (jk 😉).

A tube can be composed using an array or any traversable object.


## Middleware

Middleware is a form of an execution pipeline where
the next callable is invoked from within the current callable.

Each stage in the pipeline represents a layer, like in onions,
where the input data travels from the outermost layers towards the innermost one
and then back to the outermost layer.

```
Pipeline diagram for [Stage1, Stage2, Stage3] onion middleware:

              +--------+     +--------+     +------------+
  input   --> |        | --> |        | --> |      ---+  |
              | Stage3 |     | Stage2 |     | Stage1  |  |
  result  <-- |        | <-- |        | <-- |      <--+  |
              +--------+     +--------+     +------------+
```

This also allows to stop the pipeline execution prematurely
by not invoking the next layer.
```php
$multiplyEvenValuesBy42 = Pipeline::onion([
    // Note: this is the innermost (last) middleware (LIFO)
    function (int $val, callable $next): string {
        // core business logic...
        return $next($val * 42);
    },
    function (int $val, callable $next): string {
        // supporting logic (e.g. logging, caching, profiling performance, etc.)
        Logger::log('Input is '. $val);
        $rval = $next($val); // invoke the next middleware
        Logger::log('Output is '. $rval);
        return $rval;
    },
    // Note: this outermost middleware is executed first
    function (int $val, callable $next): string {
        // skip the next middleware if the value is not even
        if ($val % 2 !== 0) {
            return $val;
        }
        // when $val is even, invoke next middleware
        return $next($val);
    },
]);

$multiplyEvenValuesBy42(2); // 84 ; because 2 * 42 = 84
$multiplyEvenValuesBy42(3); //  3 ; because the inner middleware is not invoked
```

Sorry I could not come up with a more reasonable yet simple enough example 🤷‍♂️.

> Note that there is also a `Pipeline::invertedOnion` method,
> that treats the stages in a FIFO manner (the first middleware is executed first).

An onion pipeline can be created from an array or any traversable object.


### More on Middleware

Middleware is a design pattern that enables to dynamically add cross-cutting concerns
(supporting logic like logging, authentication, etc.)
without interrupting core business logic.
Since these concerns are handled in middleware that wrapps the core,
the core business logic can be clutter-free.
It also helps reuse this supporting logic for common tasks.

Middleware is commonly used in frameworks to allow
flexible injection of user-defined supporting logic when handling HTTP requests.\
Typical use of middleware is handling of authentication and authorization,
logging, CORS, caching, cookies, compression, etc.

![alt text](https://dab1nmslvvntp.cloudfront.net/wp-content/uploads/2013/02/middleware.jpg "Logo Title Text 1")

Besides HTTP request handling, middleware may find uses even within your business logic.
Supporting logic like logging, resource authorization, service provisioning, or caching
can be abstracted away from core logic,
improving readability of the core code and flexibility of the whole solution.

Of course there are other technique that should be considered, like events/hooks.\
As always, try to use the right tool for the job. ✌


## Fluent Builder Interface

It is trivial to implement a fluent pipeline builder.\
This library does not provide one,
but it includes a working (and tested) example of such a class.

It is used like this:
```php
$pipeline = (new OnionPipeline())
    ->pipe(function (string $passable, callable $next) {
        return $next($passable . '.');
    })
    ->pipe(function (array $passable, callable $next) {
        return $next(implode(' ', $passable));
    })
    ->pipe(function (array $passable, callable $next) {
        return $next(array_merge($passable, ['Hello', 'world']));
    })
;
$result = $pipeline->execute($input); // internally calls Pipeline::onion
// or explicitly
$result = Pipeline::onion($input);
```

If you are interested in this kind of encapsulation or interface,
see [this test case](../tests/pipes.phpt) for a working code. 
