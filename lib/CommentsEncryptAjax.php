<?php

class CommentsEncryptAjax extends CommentsEncryptBase
{
	protected $encoder;

	public function preExecute()
	{
		$html = '';

		// Get the action from the menu, only do something if this is a valid choice
		list($action, $errorMessage) = $this->getAction();
		if ($action)
		{
			// Validate the public key
			$pubKey = get_option('encdemo_pub_key');
			$this->checkPublicKey();

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
				$result = $this->doAction($action, $comment);
				
				if ($result === true)
				{
					$this->beKindToTheCpu($delay);
				}
				else
				{
					// Non-true responses are errors
					$errorMessage = $result;
				}
			}

			$html = $this->getRenderedComponent('EncryptDemoStatus', 'status');
		}

		echo json_encode(
			array(
				'count' => count($comments),
				'status_block' => $html,
				'peak_mem_usage' => memory_get_peak_usage(),
				'error' => $errorMessage,
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

	/**
	 * Gets action code and error message
	 * 
	 * @return array List in format array(action code, error message)
	 */
	protected function getAction()
	{
		$error = null;

		// Validate the action code (this will only go wrong if a naughty user injects values into the UI)
		$action = $this->getInput('action_code');
		$actionList = array(
			self::ACTION_TEST_ENCRYPT,
			self::ACTION_FULL_ENCRYPT,
			self::ACTION_FULL_DECRYPT,
			self::ACTION_ADD_HASHES,
			self::ACTION_REMOVE_HASHES,
			self::ACTION_CHECK
		);
		$ok = array_search($action, $actionList) !== false;
		if (!$ok) 
		{
			$error = 'That action is an invalid choice';
		}

		// If the action is good, see if there are any other obvious error conditions
		if ( $ok )
		{
			switch ( $action )
			{
				// Return an error if we need the privkey but it's not set
				case self::ACTION_FULL_DECRYPT:
				case self::ACTION_CHECK:
					if (!$this->getPrivateKey())
					{
						$ok = false;
						$error = 'This operation requires the private key to be logged in';
					}
			}
		}

		return array($ok ? $action : null, $error);
	}

	/**
	 * Returns delay between operations, in millions of a second
	 * 
	 * @return int
	 */
	protected function getDelaySetting()
	{
		return 10000;
	}

	/**
	 * Gets a bunch of comments to convert, depending on action
	 * 
	 * So this will get comments that are unencrypted if we're running in test mode,
	 * or will get comments that are either unencrypted and test-encrypted if we're
	 * wanting full encryption etc.
	 * 
	 * @param integer $action
	 */
	protected function getComments($action, $limit = 400)
	{
		/* @var $wpdb wpdb */
		global $wpdb;

		$sql = '';
		switch ($action)
		{
			case self::ACTION_TEST_ENCRYPT:
				// Find unencrypted comments
				$sql = "
					SELECT
						*
					FROM
						$wpdb->comments comments
					LEFT JOIN
						$wpdb->commentmeta meta ON (
							comments.comment_ID = meta.comment_id
							AND meta.meta_key = '" . self::META_ENCRYPTED . "'
						)
					WHERE
						/* i.e. no corresponding meta row */
						meta.meta_id IS NULL
						AND (
							comments.comment_author_email != ''
							OR comments.comment_author_IP != ''
						)
					LIMIT
						$limit
				";
				break;
			case self::ACTION_FULL_ENCRYPT:
			case self::ACTION_CHECK:
				// Find test-encrypted (i.e. they haven't had their plaintext data nulled yet). When we are
				// full-encrypting, for safety reasons we do not include plaintext comments
				$sql = $this->getSqlForEncryptedCommentsList($wpdb, false, $limit);
				break;
			case self::ACTION_TEST_DECRYPT:
				// Not supported at the moment
				break;
			case self::ACTION_FULL_DECRYPT:
				// Find fully-encrypted comments
				$sql = $this->getSqlForEncryptedCommentsList($wpdb, true, $limit);
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
		$error = false;

		switch ($action)
		{
			case self::ACTION_TEST_ENCRYPT:
			case self::ACTION_FULL_ENCRYPT:
				// Here's the encryption itself
				$plain = $comment->comment_author_email . "\n" . $comment->comment_author_IP;
				$encrypted = $this->encoder->encrypt($plain);

				// Here we store the data in one metadata item
				add_comment_meta(
					$comment->comment_ID,
					self::META_ENCRYPTED,
					base64_encode($encrypted),
					true
				);

				// We also store a partial hash of the key
				add_comment_meta(
					$comment->comment_ID,
					self::META_PUB_KEY_HASH,
					$this->getPublicKeyHash(),
					true
				);
				
				// Special clause for full encryption
				if ($action == self::ACTION_FULL_ENCRYPT)
				{
					
				}
				break;
			case self::ACTION_CHECK:
				$error = $this->checkComment($comment);
				break;
		}

		return $error ? $error : true;
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
		return $this->getEncoder()->getPublicKeyShortHash();
	}

	protected function beKindToTheCpu($delay)
	{
		usleep($delay);
	}

	/**
	 * Returns the current instance of the encryption module
	 * 
	 * @return EncDec
	 */
	protected function getEncoder()
	{
		return $this->encoder;
	}

	/**
	 * Mark test comments as checked
	 * 
	 * @param stdClass $comment
	 */
	protected function checkComment(stdClass $comment)
	{
		$error = null;

		// If this is the first op, clear progress
		if ($this->getInput('callback_first'))
		{
			delete_option(self::OPTION_CHECKED_MAX);
		}

		// Ensure the pub hash is the same as the stored pub key
		$thisHash = get_comment_meta($comment->comment_ID, self::META_PUB_KEY_HASH, $single = true);
		$currentHash = $this->getEncoder()->getPublicKeyShortHash();
		if ($thisHash !== $currentHash)
		{
			$id = $comment->comment_ID;
			$error = "Comment #{$id} has a public key hash of {$thisHash} but the current hash is {$currentHash}";
		}

		// Get the encrypted string
		$encrypt = get_comment_meta($comment->comment_ID, self::META_ENCRYPTED);

		//update_option(self::OPTION_CHECKED_MAX, $i);

		return $error ? $error : true;
	}
}