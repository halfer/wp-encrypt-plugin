<?php

class CommentsEncryptInit
{
	// A store for the main class, handy to set up callbacks
	protected $Main;

	public function __construct(CommentsEncryptMain $CommentsEncryptMain)
	{
		// Will be useful later on
		$this->Main = $CommentsEncryptMain;
		$root = $this->Main->getRoot();

		// Set up activation and uninstall hooks
		$pluginPath = CommentsEncryptMain::PATH_PLUGIN_NAME . '/main.php';
		register_activation_hook($pluginPath, array($this, 'activationHook'));
		register_uninstall_hook($pluginPath, array($this, 'uninstallHook'));

		// If avatars are enabled, register encryption-friendly hook
		if (get_option(CommentsEncryptMain::OPTION_STORE_AVATAR_HASHES))
		{
			require_once $root . '/lib/CommentsEncryptAvatar.php';
			$Avatar = new CommentsEncryptAvatar();
			add_filter(
				'get_avatar',
				array($Avatar, 'getAvatar')
			);
		}
	}

	/**
	 * If settings aren't detected, set some defaults up
	 */
	public function activationHook()
	{
		// Will be ignored if it already exists, which is what we want
		add_option(CommentsEncryptMain::OPTION_STORE_AVATAR_HASHES, true);
	}

	/**
	 * Delete settings when we uninstall, not when we deactivate
	 * 
	 * @todo Can we double-check with the user if any comments are still encrypted?
	 */
	public function uninstallHook()
	{
		$ok =
			delete_option(CommentsEncryptMain::OPTION_PUB_KEY) &&
			delete_option(CommentsEncryptMain::OPTION_PUB_KEY_HASH);
			delete_option(CommentsEncryptMain::OPTION_STORE_AVATAR_HASHES)
		;
	}
}