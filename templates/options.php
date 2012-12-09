<div id="encrypt-demo-options" class="wrap wp-comments-encrypt-page">
	<h2>Comment Encryption Settings</h2>

	<div class="metabox-holder">
		<div class="meta-box-sortabless">
			<div class="postbox">
				<h3 class="hndle">Encryption keys</h3>
				<div class="inside">
					<?php /* Render various states of key configuration block */ ?>
					<?php $this->renderPartial(
						'keys',
						array(
							'pubKey' => $pubKey,
							'isTested' => $isTested,
							'chooseImport' => $chooseImport,
							'chooseGen' => $chooseGen,
							'newPrivKey' => $newPrivKey,
							'isBadKey' => $isBadKey,
							'isWrongKey' => $isWrongKey,
							'isNoSaveConfirm' => $isNoSaveConfirm,
						)
					) ?>
				</div>
			</div>

			<div class="postbox">
				<h3 class="hndle">Settings</h3>
				<div class="inside">
					<?php $this->renderPartial('settings') ?>
				</div>
			</div>

			<?php /* Render status block */ ?>
			<div class="postbox">
				<h3 class="hndle">Status</h3>
				<div class="inside">
					<?php $this->renderComponent('EncryptDemoStatus', 'status') ?>
				</div>
			</div>

			<div class="postbox">
				<h3 class="hndle">Processing</h3>
				<div class="inside">
					<?php $this->renderPartial('processing') ?>
				</div>				
			</div>
		</div>
	</div>

	<div class="todo-list">
		<?php $this->renderPartial('todo') ?>
	</div>
</div>
