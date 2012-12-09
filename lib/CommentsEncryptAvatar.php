<?php

class CommentsEncryptAvatar
{
	/**
	 * If available, swap out the Gravatar hash for the correct one
	 * 
	 * @param string $html
	 * @return $html
	 */
	public function getAvatar($html)
	{
		global $comment;

		$hash = get_comment_meta($comment->comment_ID, CommentsEncryptBase::META_AVATAR_HASH);
		if ($hash)
		{
			$html = preg_replace('#[0-9a-f]{32}#', $hash, $html);
		}

		return $html;
	}
}