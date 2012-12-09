<div style="float: left; width: 49%;">
	<p>Todo:</p>
	<ol>
		<li><s>Create pub/priv pair button</s></li>
		<ol>
			<li><s>Can a pub key be regenerated from a priv key?</s></li>
		</ol>
		<li><s>Save public key in WP options</s></li>
		<li><s>Set up private key in cookie</s></li>
		<li><s>Confirm priv key is saved</s></li>
		<li><s>Create repo on Github</s></li>
		<li><s>Status box to count plaintext/test/encrypted comments</s></li>
		<li><s>Healthcheck: count number of pub keys used across all comments</s></li>
		<ol>
			<li><s>Have a few pub key hash chars per comment to achieve this</s></li>
			<li><s>Are there db calls to do counts of comments with certain metadata fields?</s></li>
			<li>Make the condition of multiple pub keys more visible as a warning (at least until
				multiple keys are supported)
		</ol>
		<li><s>Can we still use Gravatars even though we intend to empty the email field? Look at the
			"get_avatar" filter</s></li>
		<ol>
			<li>Background task to add Gravatar hashes to all comments</li>
			<li>Background task to remove Gravatar hashes to all comments</li>
			<li>Display has-hash property in comment browser, use Gravatar icon</li>
			<li><s>Add hash count to stats box</s></li>
		</ol>
		<li>Show encryption progress</li>
		<ol>
			<li>When encrypting, paint a barchart of progress</li>
			<li>Add an ETA time</li>
			<li>Add spinner</li>
		</ol>
		<li>Only try decrypting comments where there is a short hash match</li>
		<li><s>Option to import priv key manually</s></li>
		<ol>
			<li>When importing a key, display warning if the public key short hash is not found in
				existing comment meta data</li>
			<li>Add warning in comments browser where the current priv key is wrong</li>
		</ol>
		<li><s>Admin banner to say when private cookie is detected</s></li>
		<ol>
			<li>Use open/closed padlock icon</li>
			<li>Child link in admin bar to clear decryption cookie manually</li>
		</ol>
		<li>Capture logout event so we can clear all our custom cookies</li>
		<li><s>Private key login box, off search menu</s></li>
		<ol>
			<li>Validate private key against pub key on record</li>
		</ol>
		<li>Try deleting private key cookie whilst a background AJAX op is running; ensure it
			stops and reports error gracefully</li>
	</ol>
</div>
<div style="float: left; width: auto;">
	<p>Long term:</p>
	<ol>
		<li>Help blocks for options and search screens</li>
		<li>Translations</li>
		<li>Use Wordpress code conventions</li>
		<li>Allow user to choose number of bits (<a href="http://php.net/manual/en/function.openssl-pkey-new.php"
			>use configargs</a>)</li>
		<li>Collapsible blocks in options screen</li>
		<li>Warning if large numbers of comments remain unencrypted/test-encrypted for long periods</li>
		<li>Support multiple private keys?</li>
		<li>Speed tests to see if phpsec lib is faster/slower than openssl extension</li>
		<li>Offer template system as generic solution for WP plugins</li>
	</ol>
</div>