<?php

// Decide where the WP root folder is
$root = realpath(dirname(__FILE__) . '/../../..');
$loader = $root . '/wp-load.php';
if (!file_exists($loader))
{
	// Useful when developing plugin outside of WP folders - use symlink of "wp-root"
	$root = dirname(__FILE__) . '/wp-root';
	$loader = $root . '/wp-load.php';
}

require_once $loader;

$pluginRoot = dirname(__FILE__);
require_once $pluginRoot . '/lib/EncDec.php';
require_once $pluginRoot . '/components/EncryptDemoStatus.php';
require_once $pluginRoot . '/lib/EncryptTemplate.php';
require_once $pluginRoot . '/lib/AjaxHandler.php';

if (!current_user_can('manage_options'))
{
	wp_die(__('You do not have sufficient permissions to access this page.'));
}

new AjaxHandler($pluginRoot);
