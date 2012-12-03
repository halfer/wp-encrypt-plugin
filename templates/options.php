<div id="encrypt-demo-options" class="wrap">
	<h2>Encrypt options</h2>

	<div class="metabox-holder">
		<div class="meta-box-sortabless">
			<div class="postbox">
				<h3 class="hndle">Encryption keys</h3>
				<div class="inside">
					<?php /* Render various states of key configuration block */ ?>
					<?php $this->Template->renderPartial(
						'keys',
						array(
							'pubKey' => $pubKey,
							'isTested' => $isTested,
							'chooseImport' => $chooseImport,
							'chooseGen' => $chooseGen,
							'newPrivKey' => $newPrivKey,
						)
					) ?>
				</div>
			</div>

			<?php /* Render status block */ ?>
			<?php $this->Template->renderComponent('EncryptDemoStatus', 'status') ?>

			<div class="postbox">
				<h3 class="hndle">Configuration</h3>
				<div class="inside">
					<p>(Off, test only, real run. Real mode also wipes unencrypted email/ip fields)</p>

					<p>(CPU-saving settings here. Shared hosts won't like searches - very CPU intensive)</p>
				</div>
			</div>
		</div>
	</div>

	<div class="todo-list">
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
	</div>
</div>
