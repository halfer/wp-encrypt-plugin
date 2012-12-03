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
					<?php $this->Template->renderPartial('settings') ?>
				</div>
			</div>

			<?php /* Render status block */ ?>
			<div class="postbox">
				<h3 class="hndle">Status</h3>
				<div class="inside">
					<?php $this->Template->renderComponent('EncryptDemoStatus', 'status') ?>
				</div>
			</div>

			<div class="postbox">
				<h3 class="hndle">Operation mode</h3>
				<div class="inside">
					<?php $this->Template->renderPartial('operation') ?>
				</div>				
			</div>
		</div>
	</div>

	<div class="todo-list">
		<?php $this->Template->renderPartial('todo') ?>
	</div>
</div>
