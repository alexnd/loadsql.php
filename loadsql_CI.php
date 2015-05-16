<?php
/*
 * loadsql_CI.php - CodeIgniter version
 * Simple script for loading SQL files into MySQL database.
 * Have possibility to integrate into any existing CodeIgniter or Kohana3 PHP application.
 * Just copy it into document root, create db.sql and run!
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
