<div id="encrypt-demo-login" class="wrap wp-comments-encrypt-page">
	<h2>Encryption login</h2>

	<div class="metabox-holder">
		<div class="meta-box-sortabless">
			<div class="postbox">
				<h3 class="hndle">Login with private key</h3>
				<div class="inside">
					<form method="post">
						<?php if ($privKey): ?>
							<p>A private key has been set.
							<div>
								<input type="submit" name="logout" value="Unset key" />
							</div>
						<?php else: ?>
							<p>If you want to see the IP or email address of encrypted comments, or to search them,
								enter your private key here. This will be set up as a cookie on your computer, and
								thus is not easily vulnerable to theft from the server.</p>
							<textarea rows="8" cols="70" name="private_key"></textarea>
							<div>
								<input type="submit" name="login" value="Set key" />
							</div>
						<?php endif ?>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>