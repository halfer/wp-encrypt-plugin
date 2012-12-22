<div id="status-block">
	<p>This graph should (once you are happy with testing) be kept 100% green (fully encrypted):</p>

	<div class="bar-chart-block">
		<div class="bar-chart-container">
			<div class="bar-chart-bar-item bar-1"
				 style="width: <?php echo (100 - $encryptedCommentPc - $testCommentPc) . '%' ?>;"
			>
				<span class="bar-chart-bar-item-text"></span>
			</div>
			<div class="bar-chart-bar-item bar-2"
				 style="width: <?php echo $testCommentPc . '%' ?>;"
			>
				<span class="bar-chart-bar-item-text"></span>
			</div>
			<div class="bar-chart-bar-item bar-3"
				 style="width: <?php echo $encryptedCommentPc . '%' ?>;"
			>
				<span class="bar-chart-bar-item-text"></span>
			</div>
			<div class="clear"></div>
		</div>

		<div class="bar-chart-legend-container">
			<span class="bar-chart-legend-item">
				<div class="bar-chart-legend-blob bar-1"></div>
				Unencrypted: <?php echo $commentCount - $encryptedCommentCount - $testCommentCount ?>
			</span>
			<span class="bar-chart-legend-item">
				<div class="bar-chart-legend-blob bar-2"></div>
				Test encrypted: <?php echo $testCommentCount ?>
			</span>
			<span class="bar-chart-legend-item">
				<div class="bar-chart-legend-blob bar-3"></div>
				Fully encrypted: <?php echo $encryptedCommentCount ?>
			</span>
			<span class="bar-chart-legend-item">
				Total: <?php echo $commentCount ?>
			</span>
			<div class="clear"></div>
		</div>
	</div>

	<p>There are <strong><?php echo $hashCount ?></strong> Gravatar hashes in the database. These are
		required to make Gravatars work with fully encrypted comments.</p>

	<p>Of the encrypted comments, there are <?php echo $encryptionKeyCount ?> different keys in use
		(more than one is unusual).</p>
</div>