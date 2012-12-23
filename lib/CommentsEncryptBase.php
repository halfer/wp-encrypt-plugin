<?php

class CommentsEncryptBase extends TemplateSystem
{
	// Paths
	const PATH_PLUGIN_NAME = 'wp-encrypt-plugin';

	// Stores values in WP options
	const OPTION_PUB_KEY = 'encdemo_pub_key';
	const OPTION_PUB_KEY_HASH = 'encdemo_pub_key_hash';
	const OPTION_STORE_AVATAR_HASHES = 'commentsencrypt_storeavatar';

	// WP options used for non-option purposes (e.g. result caching)
	const OPTION_CHECKED_MAX = 'encdemo_checked_max';

	// Stores values in user cookies
	const COOKIE_NEW_PRIV_KEY = 'wp-encrypt-plugin_new-priv-key';
	const COOKIE_PRIV_KEY = 'wp-encrypt-plugin_priv-key';

	// Stores values in comment metadata
	const META_ENCRYPTED = 'commentenc_encrypt';
	const META_PUB_KEY_HASH = 'commentenc_pub_key_hash';
	const META_VERSION = 'commentenc_version';
	const META_AVATAR_HASH = 'commentenc_avatar';

	const KEY_BAD_KEY = 'bad_priv_key';
	const KEY_WRONG_KEY = 'wrong_priv_key';
	const KEY_NO_SAVE_CONFIRM = 'no_key_save_confirm';

	const PAGE_OPTIONS = 'options-enc';
	const PAGE_LOGIN = 'login-enc';
	const PAGE_SEARCH = 'search-enc';

	// Ajax operation codes
	const ACTION_TEST_ENCRYPT = 1;
	const ACTION_FULL_ENCRYPT = 2;
	const ACTION_TEST_DECRYPT = 3; // Not implemented
	const ACTION_FULL_DECRYPT = 4;
	const ACTION_ADD_HASHES = 5;
	const ACTION_REMOVE_HASHES = 6;
	const ACTION_CHECK = 7;

	// Used to access the same instance in various places on the comments admin screen
	protected $encoder;

	/**
	 * Returns the SQL for counting fully or test encrypted comments
	 * 
	 * @param wpdb $wpdb
	 * @param boolean $isFullyEncrypted
	 * @return string
	 */
	public function getSqlForEncryptedCommentsCount(wpdb $wpdb, $isFullyEncrypted)
	{
		return $this->getSqlForEncryptedCommentsGeneral($wpdb, 'COUNT(*)', $isFullyEncrypted);
	}

	public function getSqlForEncryptedCommentsList(wpdb $wpdb, $isFullyEncrypted, $limit)
	{
		return $this->getSqlForEncryptedCommentsGeneral($wpdb, '*', $isFullyEncrypted) . " LIMIT $limit";
	}

	public function getSqlForTestEncryptedComments( $wpdb, $limit, $maxCommentId = null)
	{
		$sql = $this->getSqlForEncryptedCommentsGeneral(
			$wpdb,
			'*',
			false,
			"AND comments.comment_ID > $maxCommentId"
		);

		return "$sql LIMIT $limit";
	}

	protected function getSqlForEncryptedCommentsGeneral(wpdb $wpdb, $columns, $isFullyEncrypted, $where = '')
	{
		$notSql = $isFullyEncrypted ? '' : 'NOT';

		return "
			SELECT
				$columns
			FROM
				$wpdb->comments comments
			INNER JOIN $wpdb->commentmeta meta ON (comments.comment_ID = meta.comment_id)
			WHERE
				meta.meta_key = '" . self::META_ENCRYPTED . "'
				AND $notSql (
					comments.comment_author_email = ''
					AND comments.comment_author_IP = ''
				)
				$where
			/* Useful if we are marking 'checked up to id X' */
			ORDER BY
				comments.comment_ID ASC
		";
	}

	/**
	 * Returns the private key cookie (or null if unset)
	 * 
	 * @return string
	 */
	protected function getPrivateKey()
	{
		return isset($_COOKIE[self::COOKIE_PRIV_KEY]) ? $_COOKIE[self::COOKIE_PRIV_KEY] : null;
	}

	protected function getPublicKey()
	{
		return get_option(self::OPTION_PUB_KEY);
	}

	/**
	 * Returns the current instance of the encryption module
	 * 
	 * Useful to the IDE; autocomplete doesn't always work with the class attribute directly
	 * 
	 * @return EncDec
	 */
	protected function getEncoder()
	{
		return $this->encoder;
	}
}