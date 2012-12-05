<?php

class EncryptDemoStatus
{
	/**
	 * Do some db queries for status block
	 * 
	 * We can't use WP metaquery methods for comments, so we need to use SQL. See:
	 * http://wordpress.org/extend/ideas/topic/add-meta_query-to-get_comments
	 * 
	 * @todo Cache the results of these for a few seconds, for speed
	 * 
	 * @global type $wpdb
	 * @return type
	 */
	public function execute()
	{
		/* @var $wpdb wpdb */
		global $wpdb;

		// Count all comments
		$sql = "SELECT COUNT(*) FROM $wpdb->comments comments";
		$commentCount = $wpdb->get_var($wpdb->prepare($sql));
		
		// Count all comments that are test-encrypted
		$sql = $this->sqlMeta($wpdb, 'NOT');
		$testCommentCount = $wpdb->get_var($wpdb->prepare($sql));

		// Count all comments that are fully encrypted
		$sql = $this->sqlMeta($wpdb, '');
		$encryptedCommentCount = $wpdb->get_var($wpdb->prepare($sql));
		
		// Count the number of different key hashes (in general we want this to be one)
		$sql = "
			SELECT
				COUNT(*)
			FROM (
				SELECT
					meta.meta_value
				FROM
					$wpdb->commentmeta meta
				WHERE
					meta.meta_key = '" . EncryptDemo::META_PUB_KEY_HASH . "'
				GROUP BY
					meta.meta_value
			) key_list
		";
		$encryptionKeyCount = $wpdb->get_var($wpdb->prepare($sql));

		return array(
			'commentCount' => $commentCount,
			'testCommentCount' => $testCommentCount,
			'encryptedCommentCount' => $encryptedCommentCount,
			'encryptionKeyCount' => $encryptionKeyCount,
		);
	}

	protected function sqlMeta(wpdb $wpdb, $not)
	{
		return "
			SELECT
				COUNT(*)
			FROM
				$wpdb->comments comments
			INNER JOIN $wpdb->commentmeta meta ON (comments.comment_ID = meta.comment_id)
			WHERE
				meta.meta_key = '" . EncryptDemo::META_ENCRYPTED . "'
				AND $not (
					comments.comment_author_email = ''
					AND comments.comment_author_IP = ''
				)
		";
	}
}