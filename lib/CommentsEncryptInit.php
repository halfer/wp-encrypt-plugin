<?php

class CommentsEncryptInit
{
	// A store for the main class, handy to set up callbacks
	protected $Main;

	public function __construct(CommentsEncryptMain $CommentsEncryptMain)
	{
		// Will be useful later on
		$this->Main = $CommentsEncryptMain;

		register_activation_hook(
			CommentsEncryptMain::PATH_PLUGIN_NAME . '/main.php',
			array($this, 'activationHook')
		);
	}

	/**
	 * If settings aren't detected, set some defaults up
	 */
	public function activationHook()
	{
		// Will be ignored if it already exists, which is what we want
		add_option(CommentsEncryptMain::OPTION_STORE_AVATAR_HASHES, true);
	}
}