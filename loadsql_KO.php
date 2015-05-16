<?php
/*
 * loadsql_KO.php - Kohana3 version
 * Simple script for loading SQL files into MySQL database.
 * Have possibility to integrate into any existing CodeIgnitor or Kohana3 PHP application.
 * Just copy it into document root, create db.sql and run!
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

//if( !isset($_SERVER['KOHANA_ENV']) && file_exists('version.info') ) {
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
