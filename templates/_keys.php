
<?php if ($isWrongKey): ?>
	<p class="error">
		That's not the correct private key - please try again.
	</p>
<?php elseif ($isBadKey): ?>
	<p class="error">
		That's not a valid key - please try again.
	</p>
<?php elseif ($isNoSaveConfirm): ?>
	<p class="error">
		You must tick the confirmation box to proceed.
	</p>	
<?php elseif ($this->getInput('imported_ok')): ?>
	<p class="succeed">
		That key imported fine, you're good to go!
	</p>	
<?php endif ?>

<?php if (false): ?>
	<p class="succeed">
		That key worked fine, and matched the public key on record. You're good to go!
	</p>
<?php endif ?>

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
		<p>Paste in your existing private key here. This is a suitable option if some of your comments are
		already encrypted, so that you don't need more than one key for their decryption.</p>
		<form method="post" action="options-general.php?page=encdemo&import_keys=1">
			<textarea name="private_key" rows="10" cols="80"></textarea>
			<div>
				<input type="submit" name="import_key_button" value="Import private key" />
			</div>
		</form>
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
			<input type="submit" name="import_keys" value="Import private key" />
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