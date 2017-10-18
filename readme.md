# Cumulus

Utilities to help with **deployment to cloud platforms**.


## UrlConfig


Class `UrlConfig` is useful for connection configurations that use URL DSNs
when an app needs separate config fields or PDO DSN,
examples:
- JawsDB MySQL or JawsDB MariaDB on Heroku

Pseudo:
```
 "mysql://john:secret@localhost:3306/my_db" => [
 		username => john
 		password => secret
 		database => my_db
 		port => 3306
 		host => localhost
 		driver => mysql
 		pdo => "mysql:host=localhost;dbname=my_db"
 ]
```

Integrates well with [DiBi](https://github.com/dg/dibi).

