<p>
	Here, you can do various things to existing comments in the database, such as encrypting, or making
	them compatible with Gravatars. Select the operation you require, and click the button! Be sure to
	leave this page open so it can do its stuff.
</p>

<p>
	Action:
	<select name="action_code" id="action_code">
		<optgroup label="Encryption actions">
			<option value="<?php echo CommentsEncryptBase::ACTION_TEST_ENCRYPT ?>">Test encrypt ordinary comments</option>
			<option value="<?php echo CommentsEncryptBase::ACTION_FULL_ENCRYPT ?>">Fully encrypt test comments</option>
			<option value="<?php echo CommentsEncryptBase::ACTION_FULL_DECRYPT ?>">Fully decrypt all comments</option>
		</optgroup>
		<optgroup label="Test actions">
			<option value="<?php echo CommentsEncryptBase::ACTION_CHECK ?>">Validate test-encrypted comments</option>
		</optgroup>
		<optgroup label="Gravatar hash actions">
			<option value="<?php echo CommentsEncryptBase::ACTION_ADD_HASHES ?>">Add missing hashes to encrypted comments</option>
			<option value="<?php echo CommentsEncryptBase::ACTION_REMOVE_HASHES ?>">Remove hashes from comments</option>
		</optgroup>
	</select>

	<?php // Spinner image for AJAX ops ?>
	<img src="<?php echo plugins_url() . '/' . CommentsEncryptBase::PATH_PLUGIN_NAME ?>/spinner.gif"
		 id="process-spinner"
		 style="display: none;"
	/>
	
	<div id="processing-status-block"></div>
</p>

<input type="submit" value="Start" id="button-start-operation" />
<input type="submit" value="Stop" id="button-stop-operation"
	   style="display: none;" />

<script type="text/javascript">
	var encryptionRunning = false;
	var callbackFirst = false;
	var callbackBusy = false;
	var timerHandle = null;

	jQuery(document).ready(function() {
		jQuery('#button-start-operation').click(function() {
			jQuery(this).hide();
			jQuery('#button-stop-operation').show();
			jQuery('#process-spinner').show();
			encryptionRunning = true;
			callbackBusy = false;
			callbackFirst = true;

			timerHandle = setInterval(encryptionCallback, 2000);

			return false;
		});
		jQuery('#button-stop-operation').click(function() {
			stopDecryption();

			return false;
		});
	});

	function stopDecryption() {
		jQuery('#button-start-operation').show();
		jQuery('#button-stop-operation').hide();
		jQuery('#process-spinner').hide();

		clearTimeout(timerHandle);
		encryptionRunning = false;
	}

	/**
	 * This is called to make the AJAX calls
	 */
	function encryptionCallback() {
		// Only launch AJAX request if an answer is not already pending
		if (!callbackBusy) {
			callbackBusy = true;
			jQuery.post(
				'<?php echo plugin_dir_url('') ?>wp-encrypt-plugin/ajax.php',
				{
					'action_code': jQuery('#action_code').val(),
					/* JS empty string evaluates to false on server; note that false and null do not */
					'callback_first': callbackFirst ? 1 : ''
				},
				ajaxSuccessCallback,
				'json'
			);
			callbackFirst = false;
		}
	}

	/**
	 * This handles the AJAX success callbacks
	 */
	function ajaxSuccessCallback(data) {
		callbackBusy = false;

		// If a number of comments were processed then...
		if (data.count > 0) {
			// ...update the specified status block
			if (data.html_block) {
				jQuery('#' + data.block_id).html(data.html_block);
			}
		} else {
			// If zero comments were processed, turn off
			stopDecryption();
		}
		
		// Handle any error replies
		if (data.error) {
			stopDecryption();
			alert('Error: ' + data.error);
		}
	}

</script>