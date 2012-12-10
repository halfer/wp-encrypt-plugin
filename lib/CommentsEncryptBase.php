<?php

class CommentsEncryptBase extends TemplateSystem
{
	// Paths
	const PATH_PLUGIN_NAME = 'wp-encrypt-plugin';

	// Stores values in WP options
	const OPTION_PUB_KEY = 'encdemo_pub_key';
	const OPTION_PUB_KEY_HASH = 'encdemo_pub_key_hash';
	const OPTION_STORE_AVATAR_HASHES = 'commentsencrypt_storeavatar';

	// Stores values in user cookies
	const COOKIE_NEW_PRIV_KEY = 'wp-encrypt-plugin_new-priv-key';
	const COOKIE_PRIV_KEY = 'wp-encrypt-plugin_priv-key';

	// Stores values in comment metadata
	const META_ENCRYPTED = 'commentenc_encrypt';
	const META_PUB_KEY_HASH = 'commentenc_pub_key_hash';
	const META_VERSION = 'commentenc_version';
	const META_AVATAR_HASH = 'commentend_avatar';

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

	/**
	 * Returns the SQL for counting fully or test encrypted comments
	 * 
	 * @param wpdb $wpdb
	 * @param boolean $isFullyEncrypted
	 * @return string
	 */
	public function getSqlForEncryptedComments(wpdb $wpdb, $isFullyEncrypted)
	{
		$notSql = $isFullyEncrypted ? '' : 'NOT';

		return "
			SELECT
				COUNT(*)
			FROM
				$wpdb->comments comments
			INNER JOIN $wpdb->commentmeta meta ON (comments.comment_ID = meta.comment_id)
			WHERE
				meta.meta_key = '" . self::META_ENCRYPTED . "'
				AND $notSql (
					comments.comment_author_email = ''
					AND comments.comment_author_IP = ''
				)
		";
	}
}