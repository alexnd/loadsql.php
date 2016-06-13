<?php
/*
 * loadsql.php
 * Simple 1-script solution for loading SQL files into MySQL database.
 * Howto:
 * 1 - Place it into document root, 
 * 2 - Provide dbconfig.php with global set of $db_* variables
 * 3 - Provide db.sql 
 * 4 - run (repeat if necessary, for example - on errors)
 * 5 - delete all unnecessary and keep some for continuous migrations^)^
 * @author: Alexander Melanchenko (info@alexnd.com)
 */

if (file_exists('dbconfig.php')) include 'dbconfig.php';

if (!isset($db_host)) $db_host = 'localhost';
if (!isset($db_login)) $db_login = 'root';
if (!isset($db_password)) $db_password = '';
if (!isset($db_name)) $db_name = 'test';
if (!isset($db_charset)) $db_charset = 'utf8';

$sqlfile = 'migrate.sql';
if ( !( file_exists($sqlfile) && is_file($sqlfile) ) ) $sqlfile = 'update.sql';
if ( !( file_exists($sqlfile) && is_file($sqlfile) ) ) $sqlfile = 'db.sql';

$debug = FALSE;
$verbose = TRUE;

set_time_limit(0);

header('Content-Type: text/plain; charset=UTF-8');

if ( !(file_exists($sqlfile) && is_file($sqlfile)) ) die('Error: cannot locate SQL file');
if ( !strlen($db_name) ) die('Error: DB name not set');

$sql = '';
#$data = file($sqlfile);
#if( count($data) )
$fp = fopen($sqlfile, 'rt');
if ($fp)
{
	$db_link = mysql_connect($db_host, $db_login, $db_password);
	if ( !$db_link )
	{
		die('Error: could not connect to db server');
	}
	if ( !mysql_select_db($db_name, $db_link) )
	{
		die('Error: could not select db '.$db_name);
	}
	if ( isset($db_charset) && $db_charset != '' ) mysql_query('SET NAMES '.$db_charset);

	$errors_count = 0;
	
	function _load_sql( $sql )
	{
		global $db_link, $debug, $verbose, $errors_count;
		if ( !$debug && $verbose )
		{
			echo 'Execute SQL:'.PHP_EOL;
		}
		if ( $verbose )
		{
			echo $sql.PHP_EOL.PHP_EOL;
		}
		if ( !$debug )
		{
			mysql_query($sql, $db_link);
			$er = mysql_errno( $db_link );
			if ( $er )
			{
				$errors_count ++;
				echo PHP_EOL.'Error:'.PHP_EOL.mysql_error( $db_link ).PHP_EOL.PHP_EOL;
			}
		}
		flush();
	}

	$comment_opened = FALSE;
	#foreach( $data as $row )
	while (!feof($fp))
	{
		#$row = trim($row);
		$row = trim(fgets($fp));
		if ( $row == '' ) continue;

		#TODO: check for comments
		if ( preg_match('!^#!', $row) || preg_match('!^--!', $row) ) continue;
		if ( $comment_opened  && preg_match('!\*\/$!', $row) )
		{
			$comment_opened = FALSE;
			continue;
		}
		elseif( !$comment_opened && preg_match('!^\/\*!', $row) )
		{
			$comment_opened = TRUE;
			continue;
		}

		if ( preg_match('!;$!', $sql) )
		{
			_load_sql( $sql );
			$sql = '';
		}
		if ( $sql != '' ) $sql .= PHP_EOL;
		$sql .= $row;
	}
	if ( $sql != '' )
	{
		_load_sql( $sql );
	}
	echo PHP_EOL.'DONE';
	if ( $errors_count ) echo PHP_EOL.$errors_count.' errors happened'.PHP_EOL;
	fclose($fp);
}

if ( $sql == '' ) die('Error: no SQL data found');
