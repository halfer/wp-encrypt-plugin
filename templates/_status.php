<div id="status_block">
	<p>There are <strong><?php echo $commentCount ?></strong> comments in the blog, of which
		<strong><?php echo $encryptedCommentCount ?></strong> are fully encrypted, and
		<strong><?php echo $testCommentCount ?></strong> are test-encrypted (i.e. the data is
		stored in plain text as well).</p>

	<p>There are <strong><?php echo $hashCount ?></strong> Gravatar hashes in the database. These are
		required to make Gravatars work with fully encrypted comments.</p>

	<p>Of the encrypted comments, there are <?php echo $encryptionKeyCount ?> different keys in use
		(more than one is unusual).</p>
</div>