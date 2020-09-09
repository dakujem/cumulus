# Middleware and Pipelines

> Since `v1.5`

Using the [`Breeze`](/src/Breeze.php) class, it is easy to create execution pipelines and middleware dispatchers.


## Tubes

"Tubes" are trivial pipelines, where every callable in a pipeline is always executed,
and the return value is passed from one callable to the input of the following one
until the end is reached.

```php
$foobarSuffix = Breeze::tube([
    function (string $a): string {
        return $a . 'foo';
    },
    function (string $a): string {
        return $a . 'bar';
    },
]);

$foobarSuffix('iam'); // 'iamfoobar'
```

Even though implementing a "tube" is as simple as a single foreach,
this helper method may be useful.


## Middleware

Middleware is a form of an execution pipeline where
the next callable is invoked from within the current callable.
This allows to stop the pipeline execution prematurely.

```php
$multiplyEvenValuesBy42 = Breeze::onion([
    // this middleware is executed last (LIFO)
    function (int $val, callable $next): string {
        // do other stuff ...
        return $next($val * 42);
    },
    // this middleware is executed first
    function (int $val, callable $next): string {
        // skip the next middleware if the value is not even
        if ($val % 2 !== 0) {
            return $val;
        }
        // when $val is even, invoke next middleware
        return $next($val);
    },
]);
```

Middleware is a design pattern that enables to add cross cutting concerns
(like logging, handling authentication, or gzip compression)
without having many code contact points.
Since these concerns are handled in middleware,
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


TODO example
provision a pieco of custom logic with a user and optionally a management console (when authorized), log the action, if user is not authenticated, skip executuion.
```php


$app = new class() {
    
    public function execute(callable $code){
        // ...
    }
};

$app->execute(function(User $u, ?AdminManagementConsole $console){
    if($console !== null){
        // do admin stuff
    } else {
        // do reguar stuff
    }
});







```

