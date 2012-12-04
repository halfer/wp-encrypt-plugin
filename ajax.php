<?php

// @todo Is there a better way to specify this folder?
$root = realpath(dirname(__FILE__) . '/../../..');
$loader = $root . '/wp-load.php';
if (file_exists($loader))
{
	// Standard plugin operation
	require_once $loader;
}
else
{
	// Useful when developing plugin outside of WP folders - use symlink of "wp-root"
	$root = dirname(__FILE__);
	$loader = $root . '/wp-root/wp-load.php';
	require_once $loader;
}

if (!current_user_can('manage_options'))
{
	wp_die(__('You do not have sufficient permissions to access this page.'));
}

class AjaxHandler
{
	// @todo Move these to a common class
	const ACTION_TEST_ENCRYPT = 1;
	const ACTION_FULL_ENCRYPT = 2;
	const ACTION_TEST_DECRYPT = 3;
	const ACTION_FULL_DECRYPT = 4;

	public function __construct()
	{
		// Validate the private key
		$this->checkPrivateKey();

		// Get the action from settings store, and validate it (nothing to do if it is off)
		$action = $this->getAction();
		$this->validateAction($action);

		// Get speed setting
		$delay = $this->getDelaySetting();

		// Process comments
		$comments = $this->getComments($action);
		foreach($comments as $comment)
		{
			$this->doAction($action, $comment);
			$this->beKindToTheCpu($delay);
		}
	}

	protected function checkPrivateKey()
	{
		
	}

	protected function getAction()
	{
		return 0;
	}

	protected function validateAction()
	{
		
	}

	protected function getDelaySetting()
	{
		
	}

	/**
	 * Gets a bunch of comments to convert, depending on action
	 * 
	 * So this will get comments that are unencrypted if we're running in test mode,
	 * or will get comments that are either unencrypted and test-encrypted if we're
	 * wanting full encryption etc.
	 * 
	 * @todo Switch hardwired value for meta_key to a class constant
	 * 
	 * @param string $action
	 */
	protected function getComments($action, $limit = 100)
	{
		/* @var $wpdb wpdb */
		global $wpdb;

		switch ($action)
		{
			case self::ACTION_TEST_ENCRYPT:
				$sql = "
					SELECT
						*
					FROM
						$wpdb->comments comments
					LEFT JOIN
						$wpdb->commentmeta meta ON (
							comments.comment_ID = meta.comment_id
							AND meta.meta_key = 'encdemo_encrypt'
						)
					WHERE
						meta.meta_id IS NULL
						AND comments.comment_author_email != ''
						AND comments.comment_author_IP != ''
					LIMIT
						$limit
				";
				break;
			case self::ACTION_FULL_ENCRYPT:
				break;
			case self::ACTION_TEST_DECRYPT:
				break;
			case self::ACTION_FULL_DECRYPT:
				break;
		}
	}

	protected function beKindToTheCpu($delay)
	{
		usleep($delay);
	}
}

echo json_encode(
	array()
);