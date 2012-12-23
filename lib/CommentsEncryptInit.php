<?php

class CommentsEncryptInit
{
	// A store for the main class, handy to set up callbacks
	protected $Main;

	public function __construct(CommentsEncryptMain $CommentsEncryptMain)
	{
		// Will be useful later on
		$this->Main = $CommentsEncryptMain;
		$this->root = $this->Main->getRoot();

		$this->initRegistrationHooks();
		$this->initAvatarRendering();
		$this->initDecryptedEmail();
		$this->initDecryptedIP();
		$this->initNewCommentsAdmin();
		$this->initMetaAction();
		$this->initScreens();
		$this->initCssQueueing();
	}

	protected function initRegistrationHooks()
	{
		// Set up activation and uninstall hooks
		$pluginPath = CommentsEncryptMain::PATH_PLUGIN_NAME . '/main.php';
		register_activation_hook($pluginPath, array($this, 'activationHook'));
		register_uninstall_hook($pluginPath, array($this, 'uninstallHook'));		
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

	protected function initAvatarRendering()
	{
		// If avatars are enabled, register encryption-friendly hook
		if (get_option(CommentsEncryptMain::OPTION_STORE_AVATAR_HASHES))
		{
			require_once $this->root . '/lib/CommentsEncryptAvatar.php';
			$Avatar = new CommentsEncryptAvatar();
			add_filter(
				'get_avatar',
				array($Avatar, 'getAvatar')
			);
		}
	}

	/**
	 * Registers a handler to decode a comment's email to plaintext
	 * 
	 * Doesn't work:
	 *	add_filter('get_comment_author_email', 'get_decrypted_email');
	 * Doesn't work:
	 *	add_filter('comment_author_email_link', 'get_decrypted_email');
	 * This works, but doesn't support html:
	 *	'comment_email'
	 */
	protected function initDecryptedEmail()
	{
		add_filter('comment_email', array($this->Main, 'getDecryptedEmail'));
	}

	/*
	 * Registers a handler to decode an IP to plaintext
	 */
	protected function initDecryptedIP()
	{
		add_filter('get_comment_author_IP', array($this->Main, 'getDecryptedIP'));
	}

	/**
	 * Registers a handler to insert a new comments admin table column
	 * 
	 * Adding a custom column in the admin interface:
	 *	http://stv.whtly.com/2011/07/27/adding-custom-columns-to-the-wordpress-comments-admin-page/
	 * Don't think it's possible to insert HTML into the author column, see here:
	 *	http://wordpress.stackexchange.com/questions/64973/is-it-possible-to-show-custom-comment-metadata-in-the-admin-panel
	 */
	protected function initNewCommentsAdmin()
	{
		add_filter('manage_edit-comments_columns', array($this->Main, 'newCommentsColumnHandler'));
		add_filter('manage_comments_custom_column', array($this->Main, 'commentColumnContentHandler'), 10, 2 );
	}

	protected function initMetaAction()
	{
		add_filter( 'comment_row_actions', array($this->Main, 'metaActionHandler'), 11, 1 );
	}

	protected function initScreens()
	{
		// Set up handler for admin screen hook
		add_action('admin_menu', array($this->Main, 'screensHandler'));

		// Set up handler for admin bar registration (100 = put menu item at the end of standard items)
		add_action('admin_bar_menu', array($this->Main, 'adminBarRegister'), 100);
	}

	protected function initCssQueueing()
	{
		// See http://codex.wordpress.org/Plugin_API/Action_Reference/admin_print_styles
		// for the reason why we're not using admin_print_styles here
		add_action('admin_enqueue_scripts', array($this->Main, 'queueCss'));
	}
}