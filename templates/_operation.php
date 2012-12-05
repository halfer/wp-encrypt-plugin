<!--
<p>
	The first operation setting is whether to encrypt comments as users enter them. This will minimise the
	length of time unencrypted comment data will be stored in the database. It is recommended to leave
	this on.
</p>

<label>
	<input type="checkbox" name="real_time" value="1" />
	Real time encryption
</label>
-->

<p>
	Here, you can encrypt your old comments. Just re-confirm all your other settings, and click the button!
	Be sure to leave this page open so it can do its stuff.
</p>

<input type="submit" value="Start encryption" id="button_start_encryption" />
<input type="submit" value="Stop encryption" id="button_stop_encryption"
	   style="display: none;" />

<script type="text/javascript">
	var encryptionRunning = false;
	var callbackBusy = false;
	var timerHandle = null;

	jQuery(document).ready(function() {
		jQuery('#button_start_encryption').click(function() {
			jQuery('#button_start_encryption').hide();
			jQuery('#button_stop_encryption').show();
			encryptionRunning = true;
			callbackBusy = false;

			timerHandle = setInterval(encryptionCallback, 2000);

			return false;
		});
		jQuery('#button_stop_encryption').click(function() {
			stopDecryption();

			return false;
		});
	});

	function stopDecryption() {
		jQuery('#button_start_encryption').show();
		jQuery('#button_stop_encryption').hide();

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
				/* @todo Yikes at hardwired path, fix this! */
				'/wp/wp-content/plugins/wp-encrypt-plugin/ajax.php',
				{},
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