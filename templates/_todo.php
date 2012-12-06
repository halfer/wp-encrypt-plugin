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
		<li><s>Option to import priv key manually</s></li>
		<ol>
			<li>When importing a key, display warning if the public key short hash is not found in
				existing comment meta data</li>
		</ol>
		<li><s>Admin banner to say when private cookie is detected</s></li>
		<ol>
			<li>Use open/closed padlock icon</li>
			<li>Child link in admin bar to clear decryption cookie manually</li>
		</ol>
		<li>Capture logout event so we can clear all our custom cookies</li>
		<li>Private key login box, off search menu</li>
	</ol>
</div>
<div style="float: left; width: auto;">
	<p>Long term:</p>
	<ol>
		<li>Help blocks for options and search screens</li>
		<li>Translations</li>
		<li>Allow user to choose number of bits (<a href="http://php.net/manual/en/function.openssl-pkey-new.php"
			>use configargs</a>)</li>
		<li>Support multiple private keys?</li>
		<li>Collapsible blocks in options screen</li>
		<li>Warning if large numbers of comments remain unencrypted/test-encrypted for long periods</li>
		<li>Speed tests to see if phpsec lib is faster/slower than openssl extension</li>
	</ol>
</div>