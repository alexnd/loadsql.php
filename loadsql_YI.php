<?php
/*
 * loadsql.php - YII Version of loadsql.php
 * Simple 1-script solution for loading SQL files into MySQL database.
 * Tested on yii2 branch, so, by default:
 * - run it from document root (web)
 * - the location of SQL-s are 1 step up from web root
 * - optionally tune db location cfg (on yii-1.x it different)
 * @author: Alexander Melanchenko (info@alexnd.com)
 */

$sqlfile = '../migrate.sql';
if( !( file_exists($sqlfile) && is_file($sqlfile) ) ) $sqlfile = '../update.sql';
if( !( file_exists($sqlfile) && is_file($sqlfile) ) ) $sqlfile = '../db.sql';

$loadsql_debug = FALSE;
$loadsql_verbose = TRUE;

if (!defined('YII_DEBUG')) define('YII_DEBUG', false);

@set_time_limit(0);

@header('Content-Type: text/plain; charset=UTF-8');

if(file_exists('../config/db.local.php')) {
	$cfg = include '../config/db.local.php';
} else if(file_exists('../config/db.php')) {
	$cfg = include '../config/db.php';
} else if(file_exists('protected/config/main.php')) {
	$cfg = include 'protected/config/main.php';
	if (isset($cfg['components']) && isset($cfg['components']['db'])) $cfg = $cfg['components']['db'];
} else {
	die('No db config found');
}

$db_login = $cfg['username'];
$db_password = $cfg['password'];
$db_charset = $cfg['charset'];

$db_dsn = '';
if (isset($cfg['dsn'])) $db_dsn = $cfg['dsn'];
if (isset($cfg['connectionString'])) $db_dsn = $cfg['connectionString'];

$m = [];
if (preg_match('/host\=([^;]+);/i', $db_dsn, $m)) {
    $db_host = $m[1];
}
if (preg_match('/dbname\=([^;]+);*/i', $db_dsn, $m)) {
    $db_name = $m[1];
}

if( !(file_exists($sqlfile) && is_file($sqlfile)) ) die('Error: cannot locate SQL file');
if( !strlen($db_name) ) die('Error: DB name not set');

$sql = '';
$data = file($sqlfile);
if( count($data) )
{
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

	$errors_count = 0;
	
	function _load_sql( $sql )
	{
		global $db_link, $loadsql_debug, $loadsql_verbose, $errors_count;
		if( !$loadsql_debug && $loadsql_verbose )
		{
			echo 'Execute SQL:'.PHP_EOL;
		}
		if( $loadsql_verbose )
		{
			echo $sql.PHP_EOL.PHP_EOL;
		}
		if( !$loadsql_debug )
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
		if( $comment_opened  && preg_match('!\*\/$!', $row) )
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