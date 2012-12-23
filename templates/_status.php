<div id="status-block">
	<p>This graph should (once you are happy with testing) be kept 100% green (fully encrypted):</p>
	
	<?php $this->renderPartial(
		'bar-chart',
		array(
			'bars' => array(
				100 - $encryptedCommentPc - $testCommentPc,
				$testCommentPc,
				$encryptedCommentPc
			),
			'labels' => array(
				array(
					'name'		=> 'Unencrypted',
					'value'		=> $commentCount - $encryptedCommentCount - $testCommentCount,
				),
				array(
					'name'		=> 'Test encrypted',
					'value'		=> $testCommentCount,					
				),
				array(
					'name'		=> 'Fully encrypted',
					'value'		=> $encryptedCommentCount,
				),
				array(
					'name'		=> 'Total',
					'value'		=> $commentCount,
					'show_blob'	=> false,
				)
			)
		)
	) ?>

	<p>There are <strong><?php echo $hashCount ?></strong> Gravatar hashes in the database. These are
		required to make Gravatars work with fully encrypted comments.</p>

	<p>Of the encrypted comments, there are <?php echo $encryptionKeyCount ?> different keys in use
		(more than one is unusual).</p>
</div>