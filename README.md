PDO Improved
============

The PDOi class is meant to act as a drop-in replacement for PDO, and supports named bound variables as well as returning native data types.


Example Problem in Native PDO
-----------------------------

```php

// Method 1, default PDO connect, returns data always as strings
$db = new PDO('mysql:dbname=test;host=127.0.0.1', 'root', '');

// Method 2, attempting to have PDO return data in native types
$db = new PDO('mysql:dbname=test;host=127.0.0.1', 'root', '', array(PDO::ATTR_STRINGIFY_FETCHES => false, PDO::ATTR_EMULATE_PREPARES => false));

$db->query('CREATE TABLE IF NOT EXISTS `test` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  PRIMARY KEY (`id`)
)');

$db->query('DELETE FROM test');
$db->query('INSERT INTO `test` (name, birthday) VALUES ("Alice", "1981-04-14"), ("Bob", "1981-01-01"), ("Charlie", "1981-02-05"), ("Donald", "1981-04-02"), ("Everett", "1981-08-04")');


$q = $db->prepare('SELECT * FROM test WHERE DATE_SUB(birthday, INTERVAL 30 DAY) < :date AND DATE_ADD(birthday, INTERVAL 30 DAY) > :date');

// Using emulated prepared statements (method 1), this works because PDO is doing string substitution.
// When switching to method 2, this fails because there are not enough parameters bound.
$q->bindValue(':date', '1981-04-07');

$q->execute();

$errorInfo = $q->errorInfo();
echo "Error Info:\n";
print_r($errorInfo);

echo "\n";
echo "Results:\n";
while($person = $q->fetch(PDO::FETCH_OBJ)) {
  var_dump($person->id);
  echo $person->name . ' ' . $person->birthday . "\n";
}

```

Output from mysql.log
---------------------

Using emulated prepared statements (method 1), we can clearly see PDO is doing string substitution, and MySQL ends up getting a string query.

```
		10116 Query	SELECT * FROM test WHERE DATE_SUB(birthday, INTERVAL 30 DAY) < '1981-04-07' AND DATE_ADD(birthday, INTERVAL 30 DAY) > '1981-04-07'
		10116 Quit	
```


The PDOi Class
--------------

The PDOi class is meant to act as a drop-in replacement for PDO, and supports named bound variables as well as returning native data types.

```php
include('PDOi.php');

$db = new PDOi('mysql:dbname=test;host=127.0.0.1', 'root', '');

$db->query('CREATE TABLE IF NOT EXISTS `test` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL,
  `birthday` date DEFAULT NULL,
  PRIMARY KEY (`id`)
)');

$db->query('DELETE FROM test');
$db->query('INSERT INTO `test` (name, birthday) VALUES ("Alice", "1981-04-14"), ("Bob", "1981-01-01"), ("Charlie", "1981-02-05"), ("Donald", "1981-04-02"), ("Everett", "1981-08-04")');

$q = $db->prepare('SELECT * FROM test WHERE DATE_SUB(birthday, INTERVAL 30 DAY) < :date AND DATE_ADD(birthday, INTERVAL 30 DAY) > :date');

// The PDOi class replaces :date in the SQL string with ?'s, then binds the variables as appropriate
$q->bindValue(':date', '1981-04-07');

$q->execute();

$errorInfo = $q->errorInfo();
echo "Error Info:\n";
print_r($errorInfo);

echo "\n";
echo "Results:\n";
while($person = $q->fetch(PDO::FETCH_OBJ)) {
  var_dump($person->id);
  echo $person->name . ' ' . $person->birthday . "\n";
}

```

Output from mysql.log
---------------------

Using real prepared statements (method 2), we can see that MySQL gets the initial statement, prepares it, then runs it with bound variables.

```
		10117 Prepare	SELECT * FROM test WHERE DATE_SUB(birthday, INTERVAL 30 DAY) < ? AND DATE_ADD(birthday, INTERVAL 30 DAY) > ?
		10117 Execute	SELECT * FROM test WHERE DATE_SUB(birthday, INTERVAL 30 DAY) < '1981-04-07' AND DATE_ADD(birthday, INTERVAL 30 DAY) > '1981-04-07'
		10117 Close stmt	
```
