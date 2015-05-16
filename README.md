loadsql
=======

Simple script (1 php file) for loading SQL files into MySQL database.

Have possibility to integrate into an existing Kohana3.x or CodeIgniter PHP application.

## Usage

* Copy loadsql.php into document root.

* Optionally, you can also take one of loadsql_XX.php, where XX are "KO" or "CI" - extensions that matches Kohana or CodeIgniter explicitly.

* Tune vars, if needed (it self-explanatory):

```
$db_host ,
$db_login ,
$db_password ,
$db_name ,
$db_charset ,
$debug ,
$verbose
```

* or uncomment desired line:

```
  require('loadsql_XX.php');
```

* Create SQL file(s) at the same dir:

  migrate.sql or update.sql or db.sql

script will search for source file in that order.

- Run loadsql.php in your browser.

**Note** This script does not checks for any security rules, so, perhaps you need to block access to it immediately after using it, or remove/rename SQL files.
