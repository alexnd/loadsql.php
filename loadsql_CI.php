<?php
/*
 * loadsql_CI.php - CodeIgniter version of loadsql.php
 * Simple 1-script solution for loading SQL files into MySQL database.
 * Just copy it into document root, tune config path, place db.sql and run!
 * @author: Alexander Melanchenko (info@alexnd.com)
 */

$debug = FALSE;
$verbose = TRUE;
 
// if you don't have next option in your index.php just set PREVENT_AUTO_BOOT to FALSE
// and fill $db_* variables below
define('PREVENT_AUTO_BOOT', TRUE);

// here is way to use your existing db config
if(defined('PREVENT_AUTO_BOOT') && PREVENT_AUTO_BOOT) {
    require 'index.php';
    if(defined('APPPATH')) {
        $path = APPPATH.'config/';
        if(defined('ENVIRONMENT') && file_exists($path.ENVIRONMENT)) $path .= ENVIRONMENT . '/';
        $path .= 'database.php';
        if( file_exists($path) && is_file($path) ) require $path;
        else die('Error: cannot find db config at '.$path);
        $db_host = $db[$active_group]['hostname'];
        $db_login = $db[$active_group]['username'];
        $db_password = $db[$active_group]['password'];
        $db_name = $db[$active_group]['database'];
        $db_charset = $db[$active_group]['char_set'];
    }
    else die('Error: cannot run without APPPATH');
}
// here is example to implement this option in your index.php (must be the last instruction in the file) :
// if ( !(defined('PREVENT_AUTO_BOOT') && PREVENT_AUTO_BOOT) ) require_once BASEPATH.'core/CodeIgniter.php';

// disable PREVENT_AUTO_BOOT and fill the $db_* vars manually, if necessary
else {
    $db_host = 'localhost';
    $db_login = 'root';
    $db_password = '';
    $db_name = 'mycms';
    $db_charset = 'utf8';
}

if(!strlen($db_name)) die('Error: DB name not set');
$db_link = mysql_connect( $db_host, $db_login, $db_password);
if( !$db_link )
{
    die('Error: could not connect to db server');
}
if( !mysql_select_db( $db_name, $db_link) )
{
    die('Error: could not select db '.$db_name);
}
if( isset($db_charset) && $db_charset != '' ) mysql_query('SET NAMES '.$db_charset);

$sqlfile = 'migrate.sql';
if( !( file_exists($sqlfile) && is_file($sqlfile) ) ) $sqlfile = 'update.sql';
if( !( file_exists($sqlfile) && is_file($sqlfile) ) ) $sqlfile = 'db.sql';
if( !( file_exists($sqlfile) && is_file($sqlfile) ) ) die('Error: cannot locate SQL file');

$sql = '';
$data = file($sqlfile);
if( count($data) )
{
    $errors_count = 0;

    function _load_sql( $sql )
    {
        global $db_link, $debug, $verbose, $errors_count;
        if( !$debug && $verbose )
        {
            echo 'Execute SQL:'.PHP_EOL;
        }
        if( $verbose )
        {
            echo $sql.PHP_EOL.PHP_EOL;
        }
        if( !$debug )
        {
            mysql_query($sql, $db_link);
            $er = mysql_errno( $db_link );
            if( $er )
            {
                $errors_count ++;
                echo PHP_EOL.'Error:'.PHP_EOL.mysql_error( $db_link ).PHP_EOL.PHP_EOL;
            }
        }
    }

    $comment_opened = FALSE;
    foreach( $data as $row )
    {
        $row = trim($row);
        if( $row == '' ) continue;

#TODO: check for comments
        if( preg_match('!^#!', $row) || preg_match('!^--!', $row) ) continue;
        if( $comment_opened && preg_match('!\*\/$!', $row) )
        {
            $comment_opened = FALSE;
            continue;
        }
        elseif( !$comment_opened && preg_match('!^\/\*!', $row) )
        {
            $comment_opened = TRUE;
            continue;
        }

        if( preg_match('!;$!', $sql) )
        {
            _load_sql( $sql );
            $sql = '';
        }
        if( $sql != '' ) $sql .= PHP_EOL;
        $sql .= $row;
    }
    if( $sql != '' )
    {
        _load_sql( $sql );
    }
    echo PHP_EOL.'DONE';
    if( $errors_count ) echo PHP_EOL.$errors_count.' errors happend'.PHP_EOL;
}

if( $sql == '' ) die('Error: no SQL data found');