<?php
/*
 * loadsql.php - Phalcon Version of loadsql.php
 * Simple 1-script solution for loading SQL files into MySQL database.
 * - run it from document root (public)
 * - the location of SQL-s are 1 step up from document root
 * - vars for tuning : $path_app and $is_dev
 * @author: Alexander Melanchenko (info@alexnd.com)
 */

$is_dev = true;
$path_app = '../app/';

$sqlfile = '../migrate.sql';
if( !( file_exists($sqlfile) && is_file($sqlfile) ) ) $sqlfile = '../update.sql';
if( !( file_exists($sqlfile) && is_file($sqlfile) ) ) $sqlfile = '../db.sql';

$loadsql_debug = FALSE;
$loadsql_verbose = TRUE;

@set_time_limit(0);

@header('Content-Type: text/plain; charset=UTF-8');

if($is_dev && file_exists($path_app.'config/development/application.php')) {
	$cfg = include $path_app.'config/development/application.php';
} else if(file_exists($path_app.'config/development/application.php')) {
	$cfg = include $path_app.'config/development/application.php';
} else {
	die('No db config found');
}

if (!isset($cfg['mysql_config'])) die('mysql_config section not found in config');

$db_login = $cfg['mysql_config']['username'];
$db_password = $cfg['mysql_config']['password'];
$db_charset = $cfg['mysql_config']['charset'];
$db_host = $cfg['mysql_config']['host'];
$db_name = $cfg['mysql_config']['dbname'];

if( !(file_exists($sqlfile) && is_file($sqlfile)) ) die('Error: cannot locate SQL file');
if( !strlen($db_name) ) die('Error: DB name not set');

$sql = '';
$fp = fopen($sqlfile, 'rt');
if ($fp)
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
		flush();
	}

	$comment_opened = FALSE;
	while (!feof($fp))
	{
		$row = trim(fgets($fp));
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
	fclose($fp);
}

if( $sql == '' ) die('Error: no SQL data found');