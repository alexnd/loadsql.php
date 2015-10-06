<?php
/*
 * loadsql_KO.php - Kohana3 version of loadsql.php
 * Simple 1-script solution for loading SQL files into MySQL database.
 * Should be called from document root
 * Tuning the location to db.sql may be required
 * @author: Alexander Melanchenko (info@alexnd.com)
 */

$debug = FALSE;
$verbose = TRUE;
$db_charset = 'utf8';
 
if(!defined('SYSPATH')) define('SYSPATH','../');
if(!class_exists('Kohana', false)) { class Kohana {
const PRODUCTION  = 10;
const STAGING     = 20;
const TESTING     = 30;
const DEVELOPMENT = 40;
static $environment;
}}

if( !isset($_SERVER['KOHANA_ENV']) ) {
	$env = 'PRODUCTION';
}
else {
	if( isset($_SERVER['KOHANA_ENV']) && in_array(strtoupper($_SERVER['KOHANA_ENV']),
		array('PRODUCTION','DEVELOPMENT','TESTING')) ) {
		$env = strtoupper($_SERVER['KOHANA_ENV']);
	}else{
		$env = 'DEVELOPMENT';
	}
}
Kohana::$environment = constant('Kohana::'.$env);
if(file_exists(SYSPATH.'application/config/'.strtolower($env).'/database.php')) {
	$config = require SYSPATH.'application/config/'.strtolower($env).'/database.php';
}
else {
	$config = require SYSPATH.'application/config/database.php';
}

// if you not supposed to use Kohana stuff - just override variables below
$db_host = isset($config['default']['connection']['hostname']) ? $config['default']['connection']['hostname'] : 'localhost';
$db_login = isset($config['default']['connection']['username']) ? $config['default']['connection']['username'] : 'root';
$db_password = isset($config['default']['connection']['password']) ? $config['default']['connection']['password'] : '';
$db_name = isset($config['default']['connection']['database']) ? $config['default']['connection']['database'] : '';

set_time_limit(0);

header('Content-Type: text/plain; charset=UTF-8');

if( !( file_exists($sqlfile) && is_file($sqlfile) ) ) die('Error: cannot locate SQL file');

$sql = '';
$data = file($sqlfile);
if( count($data) )
{
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