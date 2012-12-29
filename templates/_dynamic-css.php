<?php
/**
 * Dynamic CSS that needs to be in a PHP file
 */

$uri = plugins_url() . '/' . CommentsEncryptBase::PATH_PLUGIN_NAME;
?>

<style type="text/css">
#wpadminbar #wp-admin-bar-encdemo-key-status .locked {
	background-image: url('<?php echo $uri ?>/locked-bar.png');
}

#wpadminbar #wp-admin-bar-encdemo-key-status .unlocked {
	background-image: url('<?php echo $uri ?>/unlocked-bar.png');
}
</style>