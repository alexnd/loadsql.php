<?php
/*
 * loadsql.php
 * Simple script for loading SQL files into MySQL database using PHP mysql extension.
 * Have possibility to integrate into any existing CodeIgniter or Kohana3 PHP application.
 * Just copy it into document root, create db.sql and run!
 * @author: Alexander Melanchenko (info@alexnd.com)
 */

if(!file_exists('dbconfig.php')) die('No config found. Fill dbconfig.php or include loadsql_XX.php'); else require 'dbconfig.php';
#require 'loadsql_KO.php';
#require 'loadsql_CI.php';

if(!isset($db_host)) $db_host = 'localhost';
if(!isset($db_login)) $db_login = 'root';
if(!isset($db_password)) $db_password = '';
if(!isset($db_name)) $db_name = '';
if(!isset($db_charset)) $db_charset = 'utf8';

$sqlfile = 'migrate.sql';
if( !( file_exists($sqlfile) && is_file($sqlfile) ) ) $sqlfile = 'update.sql';
if( !( file_exists($sqlfile) && is_file($sqlfile) ) ) $sqlfile = 'db.sql';

$debug = FALSE;
$verbose = TRUE;

set_time_limit(0);

header('Content-Type: text/plain; charset=UTF-8');

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
