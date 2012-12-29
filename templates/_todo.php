<div style="float: left; width: 49%;">
	<p><strong>Todo:</strong></p>
	<ol>
		<li>Gravatars</li>
		<ol>
			<li>Display has-hash property in comment browser (use Gravatar icon)</li>
			<li>Add in percentage bar for Gravatar population</li>
		</ol>
		<li>Add JS close button to validation progress bar</li>
		<li>Make a start on the search page</li>
		<li>Private key handling</li>
		<ol>
			<li>Move per-comment pub key hash into comment string?</li>
			<li>When importing a key, display warning if the public key short hash is not found in
				existing comment meta data</li>
			<li>Add warning in comments browser where the current priv key is wrong</li>
			<li>Validate private key against pub key on record when logging on</li>
		</ol>
		<li>Admin banner</li>
		<ol>
			<li>Use open/closed padlock icon</li>
			<li>Child link in admin bar to clear decryption cookie manually</li>
		</ol>
		<li>Capture logout event so we can clear all our custom cookies</li>
		<li>Try deleting private key cookie whilst a background AJAX op is running; ensure it
			stops and reports error gracefully</li>
		<li>Only try decrypting comments where there is a short hash match</li>
		<li>Make the condition of multiple pub keys more visible as a warning (at least until
			multiple keys are supported)</li>
	</ol>
</div>
<div style="float: left; width: auto;">
	<p><strong>Long term:</strong></p>
	<ol>
		<li>Help blocks for options and search screens</li>
		<li>Translations</li>
		<li>Use Wordpress code conventions</li>
		<li>Allow user to choose number of bits (<a href="http://php.net/manual/en/function.openssl-pkey-new.php"
			>use configargs</a>)</li>
		<li>Collapsible blocks in options screen</li>
		<li>Warning if large numbers of comments remain unencrypted/test-encrypted for long periods</li>
		<li>Setting to specify how long private key cookie is remembered for</li>
		<li>Support multiple private keys?</li>
		<li>Speed tests to see if phpsec lib is faster/slower than openssl extension</li>
		<li>Use option system to cache COUNT(*) calls, as these are slow</li>
		<li>Simplify system by merging test/full encryption?</li>
		<li>Add an ETA to encryption progress</li>
	</ol>
</div>

<div style="clear: both">
	<p><strong>Done:</strong></p>
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
		</ol>
		<li><s>Gravatars</s></li>
		<ol>
			<li><s>Can we still use Gravatars even though we intend to empty the email field? Look at the
				"get_avatar" filter</s></li>
			<li><s>Background task to add Gravatar hashes to all comments</s></li>
			<li><s>Background task to remove Gravatar hashes to all comments</s></li>
			<li><s>Add hash count to stats box</s></li>
			<li><s>Add a Gravatar hash automatically when encrypting if the hash option is ticked</s></li>
			<li><s>Remove a Gravatar hash automatically when decrypting</s></li>
		</ol>
		<li><s>Show encryption progress</s></li>
		<ol>
			<li><s>When encrypting, paint a barchart of progress</s></li>
			<li><s>Add spinner</s></li>
		</ol>
		<li><s>Option to import priv key manually</s></li>
		<li><s>Admin banner to say when private cookie is detected</s></li>
		<li><s>Private key login box, off search menu</s></li>
		<li><s>Offer template system as generic solution for WP plugins</s></li>
	</ol>
</div>