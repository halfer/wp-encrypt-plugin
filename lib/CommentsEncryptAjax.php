<?php

class CommentsEncryptAjax extends CommentsEncryptBase
{
	public function preExecute()
	{
		$html = '';

		// Set up encryption class
		$this->encoder = new EncDec();

		// Get the action from the menu, only do something if this is a valid choice
		list($action, $errorMessage) = $this->getAction();
		if ($action)
		{
			// Get speed setting
			$delay = $this->getDelaySetting();

			$this->encoder->setKeysFromPrivateKey($this->getPrivateKey());

			if (!$errorMessage)
			{
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
						break;
					}
				}

				// Call cleanup to finish off this action
				$html = $this->doPostLoop($action, $comments, $result);
			}
		}

		echo json_encode(
			array(
				'count' => count($comments),
				'block_id' => ( $action == self::ACTION_CHECK ) ? 'processing-status-block' : 'status-block',
				'html_block' => $html,
				'peak_mem_usage' => memory_get_peak_usage(),
				'error' => $errorMessage,
			)
		);
	}

	/**
	 * Ensures the public key matches the one in the private key
	 */
	protected function checkPublicKey()
	{
		$error = false;

		// Check that the derived public key is the one we have on record
		$pubKey = $this->getPublicKey();
		if ($pubKey != $this->getEncoder()->getPublicKey())
		{
			$error = "The public key on record doesn't match the one inside the logged-in private key";
		}

		return $error ? $error : true;
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
				case self::ACTION_TEST_ENCRYPT:
					$this->getEncoder()->setPublicKey($this->getPublicKey());
					break;
				// Return an error if we need the privkey but it's not set
				case self::ACTION_FULL_DECRYPT:
				case self::ACTION_CHECK:
					if (!$this->getPrivateKey())
					{
						$ok = false;
						$error = 'This operation requires the private key to be logged in';
					}

					// Check that the current pub key matches the one in the logged-in priv key
					if (!$error)
					{
						$this->getEncoder()->setKeysFromPrivateKey($this->getPrivateKey());

						$result = $this->checkPublicKey();
						if ($result !== true)
						{
							$ok = false;
							$error = $result;
						}
					}
					
					break;
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
				// When we are full-encrypting, for safety reasons we do not include plaintext comments
				$sql = $this->getSqlForEncryptedCommentsList($wpdb, false, $limit);
				break;
			case self::ACTION_CHECK:
				// Find test-encrypted comments
				$maxCommentId = $this->getLastCheckedCommentId();
				$sql = $this->getSqlForTestEncryptedComments($wpdb, $limit, $maxCommentId);
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
				$error = $this->encryptComment($action, $comment);
				break;
			case self::ACTION_CHECK:
				$error = $this->checkComment($comment);
				break;
		}

		return $error ? $error : true;
	}

	protected function encryptComment($action, stdClass $comment)
	{
		// Here's the encryption itself
		$encrypted = $this->getEncoder()->encrypt(
			$this->formatStringsForEncryption(
				$comment->comment_author_email,
				$comment->comment_author_IP
			)
		);

		// Here we store the data in one metadata item
		add_comment_meta(
			$comment->comment_ID,
			self::META_ENCRYPTED,
			$encrypted,
			true
		);

		// We also store a partial hash of the key
		add_comment_meta(
			$comment->comment_ID,
			self::META_PUB_KEY_HASH,
			$this->getPublicKeyShortHash(),
			true
		);

		// Special clause for full encryption
		if ($action == self::ACTION_FULL_ENCRYPT)
		{
			// @todo
		}

		return null;
	}

	/**
	 * Formats items in a comment for encryption
	 * 
	 * @todo Accept a stdClass $comment and return a string
	 * 
	 * @param string $email
	 * @param string $ip
	 * @return string
	 */
	protected function formatStringsForEncryption($email, $ip)
	{
		return $email . "\n" . $ip;
	}

	/**
	 * Gets a partial sha1 hash of the current pub key
	 * 
	 * @todo Should we just hash the key itself, and not the ascii armour?
	 * 
	 * @return string
	 */
	protected function getPublicKeyShortHash()
	{
		return $this->getEncoder()->getPublicKeyShortHash();
	}

	protected function beKindToTheCpu($delay)
	{
		usleep($delay);
	}

	/**
	 * Mark test comments as checked
	 * 
	 * @param stdClass $comment
	 */
	protected function checkComment(stdClass $comment)
	{
		$error = null;
		$id = $comment->comment_ID;

		// Ensure the pub hash is the same as the stored pub key
		$thisHash = get_comment_meta($id, self::META_PUB_KEY_HASH, $single = true);
		$currentHash = $this->getEncoder()->getPublicKeyShortHash();
		if ($thisHash !== $currentHash)
		{
			$error = "Comment #{$id} has a public key hash of {$thisHash} but the current hash is {$currentHash}";
		}

		// Get the encrypted string
		$encrypted = get_comment_meta($id, self::META_ENCRYPTED, $single = true);
		$decrypted = $this->getEncoder()->decrypt($encrypted);
		$expectedPlain = $this->formatStringsForEncryption(
			$comment->comment_author_email,
			$comment->comment_author_IP
		);
		if ($expectedPlain == $decrypted)
		{
			// @todo For speed we should do this after the processing loop
			update_option(self::OPTION_CHECKED_MAX, $id);
		}
		else
		{
			$error = "Comment #{$id} is not encrypted correctly, or encrypted with a different key";
		}

		return $error ? $error : true;
	}

	protected function getLastCheckedCommentId()
	{
		// If this is the first op, clear progress
		if ($this->getInput('callback_first'))
		{
			update_option(self::OPTION_CHECKED_MAX, 0);
			update_option(self::OPTION_CHECKED_COUNT, 0);
		}

		return get_option(self::OPTION_CHECKED_MAX, 0);
	}

	/**
	 * Finish processing after comments loop
	 * 
	 * @param integer $action
	 * @param array $comments
	 * @param mixed $result True if okay, string error message if not
	 * @return string
	 */
	protected function doPostLoop($action, array $comments, $result)
	{
		$html = '';

		switch ($action)
		{
			case self::ACTION_TEST_ENCRYPT:
			case self::ACTION_FULL_ENCRYPT:
			case self::ACTION_TEST_DECRYPT:
			case self::ACTION_FULL_DECRYPT:
				$html = $this->getRenderedComponent('EncryptDemoStatus', 'status');
				break;
			case self::ACTION_CHECK:
				$html = $this->postLoopCheck($comments, $result);
				break;
		}

		return $html;
	}

	/**
	 * After the check action, we do this
	 * 
	 * @param array $comments
	 * @param mixed $result True if okay, string error message if not
	 * @return string
	 */
	protected function postLoopCheck(array $comments, $result)
	{
		/* @var $wpdb wpdb */
		global $wpdb;

		$html = '';

		// If everything went ok, record our progress with this block
		if ($result == true)
		{
			if ($lastComment = end($comments))
			{
				// Make a note that we've checked up to this ID (they are ordered by ID ascending)
				update_option(self::OPTION_CHECKED_MAX, $lastComment->comment_ID);

				// We've checked this number of comments
				$checkedCount = get_option(self::OPTION_CHECKED_COUNT, 0) + count($comments);
				$ok = update_option(self::OPTION_CHECKED_COUNT, $checkedCount);

				// Count total number of test comments
				$sql = $this->getSqlForEncryptedCommentsCount($wpdb, false);
				$totalCount = $wpdb->get_var($wpdb->prepare($sql));

				$html = "Checked $checkedCount of $totalCount as decryptable";
			}
		}

		return $html;
	}
}