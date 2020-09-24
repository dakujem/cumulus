# Dsn (Data Source Name)

> 📖 back to [readme](../readme.md)

- replace multiple ENV variables by a single URL DSN string
- ideal for cloud applications using remote services

Class [`Dsn`](../src/Dsn.php) is useful for connection configurations that use URL DSNs
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
