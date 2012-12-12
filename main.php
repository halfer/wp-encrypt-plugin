<?php
/*
Plugin Name: Encrypt Demo
Description: Encrypt email/IP of comments using PPK
Version: 0.1
Author: Jon Hinks
Author URI: http://blog.jondh.me.uk/
License: GPL2
*/

// Set up root folder
$root = dirname(__FILE__);

// Load template helper
require_once $root . '/vendor/TemplateSystem/TemplateSystem.php';
require_once $root . '/lib/CommentsEncryptBase.php';
require_once $root . '/lib/CommentsEncryptMain.php';

new CommentsEncryptMain($root);
