# Cumulus

A ~~set~~ pair of utilities for modern development.

Contains:
- [`Dsn`]( #dsn )
	- a DSN configuration wrapper
- [`LazyIterator`]( #lazyiterator )
	- an iterator that will wrap a collection provider function and only call it once actually needed


## Dsn

- replace multiple ENV variables for single URL DSN strings
- ideal for applications using remote services

Class `Dsn` is useful for connection configurations that use URL DSNs
when a PHP app needs separate config fields or a PDO string,
or when one simply wants to squash multiple ENV variables into one.

Examples of usage:
- database services
- remote storage services

What you'll get (pseudo):
```
"mysqli://john:secret@localhost:3306/my_db?lazy=true&connections=10" => [
		username => john
		password => secret
		database => my_db
		port => 3306
		host => localhost
 		driver => mysqli
		params => [lazy:true, connections:10]
		pdo => "mysqli:host=localhost;dbname=my_db"
]
```

To access the configration, use:
```php
$dsn = new Dsn('mysqli://john:secret@localhost/my_db');

// with optional default values
$driver = $dsn->get('driver', 'mysqli');
$port = $dsn->get('port', 3306);
```

In case the default values are not needed, it is possible to access the config props using **magic props** or **array access**:
```php
$username = $dsn->username;
$password = $dsn->password;

$host = $dsn['host'];
```

Or get the full configuration as array:
```php
$conf = $dsn->getConfig();
```

It is also possible to map the values from the URL, for example for converting the driver value from deprecated "mysql", this can be used:
```php
$dsn = new Dsn('mysql://localhost/my_db', [
	'driver' => Dsn::valueMapper(['mysql' => 'mysqli'], 'scheme'),
]);
echo $dsn->driver;  // "mysqli"
```

### Integration

`Dsn` integrates well with [DiBi]( https://github.com/dg/dibi ), Laravel,
or any other framework or stack where remote services are used.

In **Laravel**, instead of
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=homestead
DB_USERNAME=homestead
DB_PASSWORD=secret
```
one's `.env` file (or server configuration) can simply contain
```
DB_DSN=mysql://homestead:secret@127.0.0.1:3306/homestead
```

Then, your `database.php` can contain a section like this:
```php
$dsn = new Dakujem\Cumulus\Dsn(env('DB_DSN'));
return [
	'connections' => [
		'mysql' => [
			'driver' => $dsn->get('driver'),
			'host' => $dsn->get('host', '127.0.0.1'),
			'port' => $dsn->get('port', '3306'),
			'database' => $dsn->get('database', 'forge'),
			'username' => $dsn->get('username', 'forge'),
			'password' => $dsn->get('password', ''),
			// ...
		],
	]
];
```


## LazyIterator

- when an iterable collection must be passed somewhere but the collection has not yet been fetched
- when mapping of the elements of the set is needed, but the set is lazy-loaded itself (may save memory)
- useful for wrapping api calls (in certain cases)

Good, for example, when you need to pass a result of an API call
to a component iterating over the returned collection only on certain conditions
that are not directly managed at the moment of passing of the result.
In traditional way the call could be wasted.

With `LazyIterator` you can wrap the call to a callable and create LazyIterator
that is then passed to the component for rendering.
You can be sure the API only gets called when the result is actually needed.

Furthermore, you can apply a number of mapping functions in a manner similar to `array_map` function.


## Tests

Run unit tests using the following command:

`$` `composer test`

