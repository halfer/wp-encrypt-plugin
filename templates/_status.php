<div class="postbox">
	<h3 class="hndle">Status</h3>
	<div class="inside">
		<p>There are <?php echo $commentCount ?> comments in the blog, of which
			<?php echo $encryptedCommentCount ?> are fully encrypted, and
			<?php echo $testCommentCount ?> are test-encrypted (i.e. the data is stored
			in plain text as well).</p>

		<p>Of the encrypted comments, there are <?php echo $encryptionKeyCount ?> separate keys in use
			(more than one is unusual).</p>
	</div>
</div>
