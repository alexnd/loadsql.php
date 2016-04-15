loadsql
=======

Simple 1-script solution for loading SQL files into MySQL database.

Have possibility to integrate into an existing PHP applications:

- CodeIgniter

- Kohana3.x

- YII2 and YII1

- Phalcon

TODO:

- Laravel, Symfony version

## Usage

* Place loadsql.php into document root, take one of loadsql_XX.php for vendored PHP apps, where XX matches explicitly:
"KO" -Kohana, "CI" - CodeIgniter, "YI" - Yii, "PH" - Phalcon

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

* Create SQL file(s) (at the same dir, or 1-level upper, as desired on your environment):

  migrate.sql or update.sql or db.sql

script will search for source file in that order.

- Run loadsql.php in your browser.

**Note** This script does not checks for any security rules, so, perhaps you need to block access to it immediately after using it, or remove/rename SQL files.
