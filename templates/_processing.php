<p>
	Here, you can do various things to existing comments in the database, such as encrypting, or making
	them compatible with Gravatars. Select the operation you require, and click the button! Be sure to
	leave this page open so it can do its stuff.
</p>

<p>
	Action:
	<select name="action_code" id="action_code">
		<optgroup label="Encryption actions">
			<option value="<?php echo CommentsEncryptBase::ACTION_TEST_ENCRYPT ?>">Test encrypt</option>
			<option value="<?php echo CommentsEncryptBase::ACTION_FULL_ENCRYPT ?>">Fully encrypt</option>
			<option value="<?php echo CommentsEncryptBase::ACTION_FULL_DECRYPT ?>">Fully decrypt</option>
		</optgroup>
		<optgroup label="Gravatar hash actions">
			<option value="<?php echo CommentsEncryptBase::ACTION_ADD_HASHES ?>">Add hashes</option>
			<option value="<?php echo CommentsEncryptBase::ACTION_REMOVE_HASHES ?>">Remove hashes</option>
		</optgroup>
	</select>
</p>

<input type="submit" value="Start" id="button_start_operation" />
<input type="submit" value="Stop" id="button_stop_operation"
	   style="display: none;" />

<script type="text/javascript">
	var encryptionRunning = false;
	var callbackBusy = false;
	var timerHandle = null;

	jQuery(document).ready(function() {
		jQuery('#button_start_operation').click(function() {
			jQuery(this).hide();
			jQuery('#button_stop_operation').show();
			encryptionRunning = true;
			callbackBusy = false;

			timerHandle = setInterval(encryptionCallback, 2000);

			return false;
		});
		jQuery('#button_stop_operation').click(function() {
			stopDecryption();

			return false;
		});
	});

	function stopDecryption() {
		jQuery('#button_start_operation').show();
		jQuery('#button_stop_operation').hide();

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
					'action_code': jQuery('#action_code').val()
				},
				ajaxSuccessCallback,
				'json'
			);
		}
	}

	/**
	 * This handles the AJAX success callbacks
	 */
	function ajaxSuccessCallback(data) {
		callbackBusy = false;

		// If a number of comments were processed then...
		if (data.count > 0) {
			// ...update the html status block
			if (data.status_block) {
				jQuery('#status_block').html(data.status_block);
			}
		} else {
			// If zero comments were processed, turn off
			stopDecryption();
		}
	}

</script>