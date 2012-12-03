<p>Todo:</p>
<ol>
	<li><s>Create pub/priv pair button</s></li>
	<li>Option to add priv key manually</li>
	<ol>
		<li><s>Can a pub key be regenerated from a priv key?</s></li>
	</ol>
	<li>Allow user to choose number of bits (<a href="http://php.net/manual/en/function.openssl-pkey-new.php"
		>use configargs</a>)</li>
	<li><s>Save public key in WP options</s></li>
	<li><s>Set up private key in cookie</s></li>
	<li><s>Confirm priv key is saved</s></li>
	<li><s>Create repo on Github</s></li>
	<li><s>Status box to count plaintext/test/encrypted comments</s></li>
	<li>Healthcheck: count number of pub keys used across all comments (if more than one, then report
		it as a problem in UI)</li>
	<ol>
		<li>Have a few pub key hash chars per comment to achieve this?</li>
		<li><s>Are there db calls to do counts of comments with certain metadata fields?</s></li>
	</ol>
	<li><s>Admin banner to say when private cookie is detected</s></li>
	<li>Speed tests to see if phpsec lib is faster/slower than openssl extension</li>
	<li>Capture logout event so we can clear all our custom cookies</li>
	<li>Help blocks for options and search screens</li>
</ol>