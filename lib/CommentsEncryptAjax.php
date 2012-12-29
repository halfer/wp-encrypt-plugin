<?php

class CommentsEncryptAjax extends CommentsEncryptBase
{
	public function preExecute()
	{
		$html = '';
		$count = 0;

		// Set up encryption class
		$this->encoder = new AssymetricEncryptor();

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
				$resultBlock = $this->doPostLoop($action, $comments, $result);
				$html = $resultBlock['html'];

				// Some post actions will return a count value
				$count = array_key_exists('count', $resultBlock) ? $resultBlock['count'] : count($comments);
			}
		}

		echo json_encode(
			array(
				'count' => $count,
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
		$wpdb = $this->getGlobalDatabase();

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
				case self::ACTION_FULL_DECRYPT:
				case self::ACTION_CHECK:
				case self::ACTION_FULL_ENCRYPT:
					// Return an error if we need the privkey but it's not set
					list($ok, $error) = $this->requirePrivateKey();
					break;
				case self::ACTION_ADD_HASHES:
					// Require the privkey only if there are fully encrypted items
					$sql = $this->getSqlForEncryptedCommentsCount($wpdb, true);
					$encryptedCount = $wpdb->get_var($wpdb->prepare($sql));
					if ($encryptedCount)
					{
						list($ok, $error) = $this->requirePrivateKey();					
					}
					break;
			}
		}

		return array($ok ? $action : null, $error);
	}

	protected function requirePrivateKey()
	{
		$ok = true;
		$error = null;

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

		return array($ok, $error);
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
		$wpdb = $this->getGlobalDatabase();

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
			case self::ACTION_ADD_HASHES:
				$sql = $this->getSqlForEncryptedUnhashedComments($wpdb, $limit);
				break;
			case self::ACTION_REMOVE_HASHES:
				// Deliberately do nothing
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
				$error = $this->encryptComment($comment);
				break;
			case self::ACTION_FULL_ENCRYPT:
			case self::ACTION_CHECK:
				$error = $this->checkComment($comment);
				if ($action == self::ACTION_FULL_ENCRYPT && !$error)
				{
					$error = $this->emptyPlaintextValues($comment);
				}
				break;
			case self::ACTION_FULL_DECRYPT:
				$error = $this->decryptComment($comment);
				break;
			case self::ACTION_ADD_HASHES;
				$error = $this->addHashToComment($comment);
				break;
		}

		return $error ? $error : true;
	}

	/**
	 * Encrypts the test comment, leaving the plaintext fields as they are
	 * 
	 * @todo Add in db format identifier, something like "<version><newline><encrypted-data>"
	 * 
	 * @param stdClass $comment
	 * @return mixed False if everything was ok, string error if not
	 */
	protected function encryptComment(stdClass $comment)
	{
		// Here's the encryption itself
		$encrypted = $this->getEncoder()->encrypt(
			$this->formatStringsForEncryption($comment)
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

		return null;
	}

	/**
	 * Formats items in a comment for encryption
	 * 
	 * @param stdClass $comment
	 * @return string
	 */
	protected function formatStringsForEncryption(stdClass $comment)
	{
		return $comment->comment_author_email . "\n" . $comment->comment_author_IP;
	}

	protected function splitStringForDecryption($decryptedString)
	{
		// We don't want to trim this first, since an email entry could be a null string
		$strings = explode("\n", $decryptedString);

		return array($strings[0], $strings[1]);
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
	 * Ensure test comment can be decrypted
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
		$expectedPlain = $this->formatStringsForEncryption($comment);
		if ($expectedPlain != $decrypted)
		{
			$error = "Comment #{$id} is not encrypted correctly, or encrypted with a different key";
		}

		return $error;
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
	 * If the check was successful, and we're full-encrypting, remove the email/IP
	 * 
	 * Tried wp_update_comment(), but that doesn't seem to work on the IP field
	 * 
	 * @param stdClass $comment
	 */
	protected function emptyPlaintextValues(stdClass $comment)
	{
		$ok = $this->updateComment($comment, '', '');

		return $ok ? null : "Failed to re-save comment #{$comment->comment_ID}";
	}

	/**
	 * Updates the email and IP fields of a comment in the database
	 * 
	 * @param stdClass $comment
	 * @return boolean True if successful
	 */
	protected function updateComment(stdClass $comment, $email, $ip)
	{
		$wpdb = $this->getGlobalDatabase();
		$rowsAffected = $wpdb->update(
			$wpdb->comments,
			array(
				'comment_author_email' => $email,
				'comment_author_IP' => $ip,
			),
			array('comment_ID' => $comment->comment_ID,)
		);

		return $rowsAffected !== false;
	}

	/**
	 * Decrypts a comment fully
	 * 
	 * @todo Implement separate encryption class that splits email/IP in a central place
	 * 
	 * @param stdClass $comment
	 * @return mixed Bool false if everything was okay, otherwise a string error message
	 */
	protected function decryptComment(stdClass $comment)
	{
		$encrypted = get_comment_meta($comment->comment_ID, self::META_ENCRYPTED, $single = true);
		$decrypted = $this->getEncoder()->decrypt($encrypted);
		if ($decrypted)
		{
			list($email, $ip) = $this->splitStringForDecryption($decrypted);
			$ok = $this->updateComment($comment, $email, $ip);
			// @todo Still need to remove comment_meta fields
			// @todo Run the validation checker on it before zapping the meta fields
		}

		return $ok ? false : "Could not decrypt comment #{$comment->comment_ID}";
	}

	/**
	 * Adds a gravatar hash to a fully encrypted comment
	 * 
	 * @param stdClass $comment
	 */
	protected function addHashToComment(stdClass $comment)
	{
		$ok = false;

		$encrypted = get_comment_meta($comment->comment_ID, self::META_ENCRYPTED, $single = true);
		$decrypted = $this->getEncoder()->decrypt($encrypted);
		if ($decrypted)
		{
			list($email, $ip) = $this->splitStringForDecryption($decrypted);
			$hash = md5($email);
			$ok = add_comment_meta($comment->comment_ID, self::META_AVATAR_HASH, $hash, $_unique = true);
		}

		return $ok ? false : "Failed to hash the email field for comment #{$comment->comment_ID}";
	}

	/**
	 * Finish processing after comments loop
	 * 
	 * @param integer $action
	 * @param array $comments
	 * @param mixed $result True if okay, string error message if not
	 * @return array
	 */
	protected function doPostLoop($action, array $comments, $result)
	{
		$return = array();

		switch ($action)
		{
			case self::ACTION_TEST_ENCRYPT:
			case self::ACTION_FULL_ENCRYPT:
			case self::ACTION_TEST_DECRYPT:
			case self::ACTION_FULL_DECRYPT:
			case self::ACTION_ADD_HASHES:
				$return['html'] = $this->getRenderedComponent('EncryptDemoStatus', 'status');
				break;
			case self::ACTION_REMOVE_HASHES:
				$return = $this->postLoopRemoveHashes();
				break;
			case self::ACTION_CHECK:
				$return['html'] = $this->postLoopCheck($comments, $result);
				break;
		}

		return $return;
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
		$wpdb = $this->getGlobalDatabase();
		$html = '';

		// If everything went ok, record our progress with this block
		if ($result === true)
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

				// Get pc of checked comments
				$checkedPc = floor(1000 * $checkedCount / $totalCount) / 10;

				// Render an HTML bar chart of validated vs unvalidated
				$html = $this->getRenderedPartial(
					'bar-chart',
					array(
						'bars' => array(
							$checkedPc,
							100 - $checkedPc,
						),
						'labels' => array(
							array(
								'name'		=> 'Validated',
								'value'		=> $checkedCount,
							),
							array(
								'name'		=> 'Not validated',
								'value'		=> $totalCount - $checkedCount,
							),
							array(
								'name'		=> 'Total',
								'value'		=> $totalCount,
								'show_blob'	=> false,
							)
						),
					)
				);
			}
		}

		return $html;
	}

	/**
	 * Removes all metadata comment hashes from the db
	 * 
	 * @todo Return a 'stop_immediately' flag so the AJAX op doesn't perform another try
	 * 
	 * @return string
	 */
	protected function postLoopRemoveHashes()
	{
		// Delete all metadata hash entries
		$wpdb = $this->getGlobalDatabase();
		$rowsAffected = $wpdb->delete($wpdb->commentmeta, array('meta_key' => self::META_AVATAR_HASH));

		// Grab a new status block
		$html = $this->getRenderedComponent('EncryptDemoStatus', 'status');

		return array('html' => $html, 'count' => $rowsAffected);
	}

	/**
	 * Hides the global variable access from other methods
	 * 
	 * @global wpdb $wpdb
	 * @return wpdb
	 */
	protected function getGlobalDatabase()
	{
		global $wpdb;

		return $wpdb;
	}
}