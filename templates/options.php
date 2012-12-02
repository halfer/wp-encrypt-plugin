<div id="encrypt-demo-options" class="wrap">
	<h2>Encrypt options</h2>

	<div class="metabox-holder">
		<div class="meta-box-sortabless">
			<div class="postbox">
				<h3 class="hndle">Encryption keys</h3>
				<div class="inside">
					<?php if ($pubKey): ?>
						<p>This is your current public key:</p>

						<pre><?php echo $pubKey ?></pre>
							
						<?php if ($isTested): ?>
							<p>The key has been detected as working fine.</p>
							<form method="post">
								<div>
									<input type="submit" name="start_again" value="Delete this key" />
								</div>
							</form>
						<?php else: ?>
							<p>Now we need to test this key. To do so, enter your private key here:</p>

							<form method="post">
								<textarea name="private_key" rows="7" cols="80"></textarea>
								<div>
									<input type="submit" name="test_key" value="Test private key" />
									<input type="submit" name="start_again" value="Delete this key" />
								</div>
							</form>
						<?php endif ?>
					<?php else: ?>
						<?php if ($chooseImport): ?>
							<p>Suitable warning!</p>
							<textarea
								rows="10"
								cols="80">Public key goes here</textarea>
						<?php elseif ($chooseGen): ?>
							<p>The system has generated a set of keys for you, and the one you need to save
							to your computer is printed here. This has <strong>not</strong> yet been installed. Once you have
							confirmed below that you have taken a copy, you can click the Install button.</p>
							<pre><?php echo $newPrivKey ?></pre>
							<form method="post">
								<p>
									<label>
										<input type="checkbox" name="save_confirm" value="1" />
										I confirm I have taken a permanent copy of this private key
									</label>
								</p>
								<input type="submit" name="gen_keys_install" value="Install new keys" />
							</form>
						<?php else: ?>
							<?php /* We have no public key stored, so we need offer options to create/import */ ?>
							<p>This system requires text-based keys to operate, one of which is known as the "public key",
								which is stored on the server, and the other of which is known as the "private key",
								which you keep on your personal computer. The public key is used to encrypt comment data
								(i.e. email and IP) and the private key is used to decrypt.</p>
							
							<p>If you're starting from scratch, generate new keys. However if you've an existing private
								key and wish to import it, then you can do so here.</p>
							<form method="get">
								<input type="hidden" name="page" value="encdemo" />
								<input type="submit" name="gen_keys" value="Generate new keys" />
								<input type="submit" name="import_keys" value="Import keys" />
							</form>
						<?php endif ?>
					<?php endif ?>
					<!--
					<p>The public key is used to encrypt user IP and email addresses. Make
						<strong>absolutely</strong> sure you have a copy of the correct corresponding
						private key, stored in a safe place, so you can decrypt this data on demand.</p>

					<p>Do not store your private key on the server, or leave it in a mail account on
						the same server, otherwise the encryption can be trivially defeated.</p>
					-->
				</div>
			</div>

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
			<li>Healthcheck: count number of pub keys used across all comments (if more than one, then report
				it as a problem in UI)</li>
			<ol>
				<li>Have a few pub key hash chars per comment to achieve this?</li>
			</ol>
			<li>Admin banner to say when private cookie is detected</li>
			<li>Speed tests to see if phpsec lib is faster/slower than openssl extension</li>
			<li>Capture logout event so we can clear all our custom cookies</li>
		</ol>
	</div>
</div>
