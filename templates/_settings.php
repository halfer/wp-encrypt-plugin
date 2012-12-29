<form method="post">
	
	<?php /* Disabled, as we're not saving/loading these settings yet
	<p>You may also set up a CPU load setting. This is set to slow initially, which is recommended
		for shared hosting or if your web site performance would be adversely affected. Remember that if
		you regularly consume a lot of CPU on shared hosts, you may be asked to upgrade to a VPS. If you're
		not sure about this setting, leave it on slow.</p>

	<select>
		<option value="slow">Slow searches, low load</option>
		<option value="medium">Faster searches, moderate load</option>
		<option value="fast">Fast searches, high load</option>
	</select>
	*/ ?>

	<p>Choose whether to save email hashes, which are used to generate Gravatars. Whilst an email address
	cannot be reversed-engineered from a hash, it may provide a search-string to aid searching for a
	commenter's identity elsewhere, if your security opponent is sufficiently determined. In most cases
	however, if your theme uses Gravatars, it is recommended to leave this enabled.</p>

	<label>
		<input type="checkbox" name="save_avatar_hashes" value="1"
			<?php echo get_option(CommentsEncryptMain::OPTION_STORE_AVATAR_HASHES) ? 'checked="checked"' : '' ?>
		/>
		Store avatar hashes
	</label>

	<p>
		<input type="submit" name="save_settings" value="Save settings" />
	</p>
</form>