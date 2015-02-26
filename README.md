# PHPSqliteArray
PHP Array implementation that uses SQLite as it's underlying storage.

Usage:
--------------

```PHP
<?php
  require("sqlitearray.php");

  $array = new SQLiteArray();
  $array[] = "something";
  $array[] = 1234;
  $array[] = new DateTime(); // Notice, objects are serialized/unserialized.
  $array["key"] = "blabla";

  $string = "blabla";
  $array[$string] = "123";

  var_dump(isset($array[$string]));
  var_dump(isset($array["idontexists"]));

  unset($array[$string]);
  var_dump(isset($array[$string]));

  // Code like this DOES NOT WORK: array_search("blabla", $array);

  echo "\nITERATION\n";
  foreach ($array as $k => $v) { echo "*** $k\n"; var_dump($v); }
```
