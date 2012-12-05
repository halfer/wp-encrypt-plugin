<?php

class AjaxHandler extends EncryptTemplate
{
	// @todo Move these to a common class
	const ACTION_TEST_ENCRYPT = 1;
	const ACTION_FULL_ENCRYPT = 2;
	const ACTION_TEST_DECRYPT = 3;
	const ACTION_FULL_DECRYPT = 4;

	protected $encoder;

	public function preExecute()
	{
		// Validate the public key
		$pubKey = get_option('encdemo_pub_key');
		$this->checkPublicKey();

		// Get the action from settings store, and validate it (nothing to do if it is off)
		$action = $this->getAction();
		$this->validateAction($action);

		// Get speed setting
		$delay = $this->getDelaySetting();

		// Set up encryption class
		// @todo Switch the option string to a constant from another class
		$this->encoder = new EncDec();
		$this->encoder->setPublicKey($pubKey);

		// Process comments
		$comments = $this->getComments($action);
		foreach($comments as $comment)
		{
			$this->doAction($action, $comment);
			$this->beKindToTheCpu($delay);
		}

		// Init the template system here, so we can render the _status component/partial
		// and inject the result

		echo json_encode(
			array(
				'count' => count($comments),
				'peak_mem_usage' => memory_get_peak_usage(),
			)
		);
	}

	/**
	 * Ensures that we have a public key
	 */
	protected function checkPublicKey()
	{
		// Does nothing at the moment
	}

	protected function getAction()
	{
		return self::ACTION_TEST_ENCRYPT;
	}

	/**
	 * Exits with an error if mode = off
	 */
	protected function validateAction()
	{
		// Does nothing at the moment
	}

	/**
	 * Returns delay between operations, in millions of a second
	 * 
	 * @return int
	 */
	protected function getDelaySetting()
	{
		return 1000;
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
	protected function getComments($action, $limit = 500)
	{
		/* @var $wpdb wpdb */
		global $wpdb;

		$sql = '';
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

		$rows = null;
		if ($sql)
		{
			$rows = $wpdb->get_results($wpdb->prepare($sql));
		}

		return is_array($rows) ? $rows : array();
	}

	protected function doAction($action, stdClass $comment)
	{
		switch ($action)
		{
			case self::ACTION_TEST_ENCRYPT:
			case self::ACTION_FULL_ENCRYPT:
				// Here's the encryption itself
				$email = $this->encoder->encrypt($comment->comment_author_email);
				$ip = $this->encoder->encrypt($comment->comment_author_IP);

				// Here we store the data in one metadata item
				add_comment_meta(
					$comment->comment_ID,
					'encdemo_encrypt',
					$email . "\n" . $ip,
					true
				);

				// We also store a partial hash of the key
				add_comment_meta(
					$comment->comment_ID,
					'encdemo_pub_key_hash',
					$this->getPublicKeyHash(),
					true
				);
				break;
		}
	}

	/**
	 * Gets a partial sha1 hash of the current pub key
	 * 
	 * @todo Should we just hash the key itself, and not the ascii armour?
	 * 
	 * @return string
	 */
	protected function getPublicKeyHash()
	{
		$key = trim($this->encoder->getPublicKey());

		return substr(sha1($key), 0, 12);
	}

	protected function beKindToTheCpu($delay)
	{
		usleep($delay);
	}
}