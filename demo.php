<?php
/*
Plugin Name: Encrypt Demo
Plugin URI: http://blog.jondh.me.uk/encrypt-demo
Description: Encrypt email/IP of comments using PPK
Version: 0.1
Author: Jon Hinks
Author URI: http://blog.jondh.me.uk/
License: GPL2
*/

// Set up root folder
$root = dirname(__FILE__);

// Load template helper
require_once $root . '/lib/EncryptTemplate.php';
require_once $root . '/lib/EncryptDemo.php';

new EncryptDemo($root);
