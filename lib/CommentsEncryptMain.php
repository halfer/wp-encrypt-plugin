<?php

class CommentsEncryptMain extends CommentsEncryptBase
{
	// Used to pass template rendering values around this object
	protected $templateVars = array();

	// Used to access the same instance in various places on the comments admin screen
	protected $encoder;

	public function preExecute()
	{
		// Set up init object, removes some clutter from the main class
		require_once $this->root . '/lib/CommentsEncryptInit.php';
		new CommentsEncryptInit($this);

		// Initialisation
		$this->initDecryptedEmail();
		$this->initDecryptedIP();
		$this->initNewCommentsAdmin();
		$this->initMetaAction();
		$this->initScreens();
		$this->initCssQueueing();
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
		add_filter('comment_email', array($this, 'getDecryptedEmail'));
	}

	/*
	 * Registers a handler to decode an IP to plaintext
	 */
	protected function initDecryptedIP()
	{
		add_filter('get_comment_author_IP', array($this, 'getDecryptedIP'));
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
		add_filter('manage_edit-comments_columns', array($this, 'newCommentsColumnHandler'));
		add_filter('manage_comments_custom_column', array($this, 'commentColumnContentHandler'), 10, 2 );
	}

	protected function initMetaAction()
	{
		add_filter( 'comment_row_actions', array($this, 'metaActionHandler'), 11, 1 );
	}

	protected function initScreens()
	{
		// Set up handler for admin screen hook
		add_action('admin_menu', array($this, 'screensHandler'));

		// Set up handler for admin bar registration (100 = put menu item at the end of standard items)
		add_action('admin_bar_menu', array($this, 'adminBarRegister'), 100);
	}

	protected function initCssQueueing()
	{
		// See http://codex.wordpress.org/Plugin_API/Action_Reference/admin_print_styles
		// for the reason why we're not using admin_print_styles here
		add_action('admin_enqueue_scripts', array($this, 'queueCss'));
	}

	public function adminBarRegister(WP_Admin_Bar $WpAdminBar)
	{
		$privKeySet = (bool) $this->getPrivateKey();

		$WpAdminBar->add_menu(
			array(
				'id' => 'encdemo_key_status',
				'title' => $privKeySet? 'Private key set' : 'Private key unknown',
				'href' => 'edit-comments.php?page=' . self::PAGE_LOGIN,
			)
		);
	}

	/**
	 * Public method to decrypt & read the email on a comment
	 * 
	 * @return string
	 */
	public function getDecryptedEmail()
	{
		global $comment;

		// Only decrypt if we actually have a cookie key
		$email = '';
		if ($privKey = $this->getPrivateKey())
		{
			$this->decryptComment($this->encoder, $comment);
			$email = $comment->decrypted->comment_author_email;
		}

		return $email;
	}

	public function getDecryptedIP()
	{
		global $comment;

		// Only decrypt if we actually have a cookie key
		$ip = '';
		if ($privKey = $this->getPrivateKey())
		{
			$this->decryptComment($this->encoder, $comment);
			$ip = $comment->decrypted->comment_author_IP;
		}

		return $ip;
	}

	protected function decryptComment(EncDec $EncDec, stdClass $comment)
	{
		if (!isset($comment->decrypted))
		{
			$encrypted = get_comment_meta( $comment->comment_ID, self::META_ENCRYPTED, true );
			$unpacked = base64_decode($encrypted);
			$plain = $EncDec->decrypt($unpacked);
			$comment->decrypted = new stdClass();
			list(
				$comment->decrypted->comment_author_email,
				$comment->decrypted->comment_author_IP
			) = explode("\n", $plain);
		}
	}

	/**
	 * Callback to modify the columns offered in the comments UI
	 * 
	 * @param array $columns
	 * @return array
	 */
	public function newCommentsColumnHandler($columns)
	{
		$columns = array_merge(
			array_slice($columns, 0, 2),
			array('encrypt' => __( 'Encrypted' )),
			array_slice($columns, 2)
		);

		return $columns;		
	}

	/**
	 * Renders an encryption status for the specified comment
	 * 
	 * @param string $column
	 * @param integer $commentId
	 */
	public function commentColumnContentHandler($column, $commentId)
	{
		if ( 'encrypt' == $column ) {
			$comment = get_comment($commentId);
			$metaEncrypted = get_comment_meta($commentId, self::META_ENCRYPTED);

			$uri = plugins_url() . '/' . self::PATH_PLUGIN_NAME;
			if ($comment->comment_author_email && $comment->comment_author_IP)
			{
				if ($metaEncrypted)
				{
					echo '<img src="' . $uri . '/lock.png" /> Test';
				}
				else
				{
					echo '<img src="' . $uri . '/lock.png" /> No';					
				}
			}
			else
			{
				echo '<img src="' . $uri . '/lock.png" /> Yes';	
			}
		}
	}

	/**
	 * Renders the 'Ban IP X' by decoding the IP in the comment
	 * 
	 * @todo Fix dummy output
	 * 
	 * @param array $actions
	 * @return string
	 */
	public function metaActionHandler($actions)
	{
		// Access the comment thus:
		//global $comment;
		//echo get_comment_meta( $comment->comment_ID, 'town', true );

		$actions['ip_ban'] = '<a href="#">Ban IP X</a>';

		return $actions;
	}

	public function screensHandler()
	{
		// This does some variable initialisation for the admin screen decryption. (I'm using admin_head here
		// as it's the best thing I could think of where get_current_screen() actually works; admin_init is
		// too early). 
		add_action('admin_head', array($this, 'commentsActionHandler'));

		// Add settings menu item
		$hookSuffix = add_options_page(
			'Comment Encryption Options',
			'Comment Encryption',
			'manage_options',
			self::PAGE_OPTIONS,
			array($this, 'optionsScreenHandler')
		);

		// Add an actions handler for the options page
		add_action('load-' . $hookSuffix, array($this, 'optionsActionHandler'));

		// Add submenu page for login facility
		$hookSuffix = add_submenu_page(
			'edit-comments.php',
			'Login with private key',
			'Login',
			'moderate_comments',
			self::PAGE_LOGIN,
			array($this, 'loginScreenHandler')
		);

		// Add an actions handler for the login page
		add_action('load-' . $hookSuffix, array($this, 'loginActionHandler'));

		// Add submenu page for search facility
		add_submenu_page(
			'edit-comments.php',
			'Search encrypted email/IP',
			'Search encrypted',
			'moderate_comments',
			self::PAGE_SEARCH,
			array($this, 'searchScreenHandler')
		);
	}

	/**
	 * Registers CSS styles for our pages only
	 * 
	 * @param string $hook
	 */
	public function queueCss($hook)
	{
		$options = 'settings_page_' . self::PAGE_OPTIONS;
		$login = 'comments_page_' . self::PAGE_LOGIN;

		if ($hook == $options || $hook == $login)
		{
			// Get plugins folder relative to wp root
			$site = site_url();
			$relativePath = substr(plugins_url(), strlen($site));

			wp_register_style(
				'encdemo_css',
				$relativePath . '/' . self::PATH_PLUGIN_NAME . '/styles/main.css'
			);
			wp_enqueue_style('encdemo_css');
		}
	}

	public function commentsActionHandler()
	{
		$screen = get_current_screen();
		if ($screen->base == 'edit-comments')
		{
			// In the comments screen, we need to prepare for decryption
			require_once $this->root . '/lib/EncDec.php';

			if ($privKey = $this->getPrivateKey())
			{
				$this->encoder = new EncDec();
				// @todo Do we need to handle an ok = false here?
				$ok = $this->encoder->setKeysFromPrivateKey($privKey);
			}
		}
	}

	/**
	 * Actions handler called prior to options screen rendering
	 * 
	 * Needs to be early so we can set cookies if we wish
	 */
	public function optionsActionHandler()
	{
		// Read public key from WP
		$pubKey = get_option(self::OPTION_PUB_KEY);

		// Find if this pub key has been tested
		$isTested = sha1($pubKey) == get_option(self::OPTION_PUB_KEY_HASH);

		// Get some user input, untainting where appropriate
		$chooseImport = (bool) $this->getInput('import_keys');
		$chooseGen = (bool) $this->getInput('gen_keys');
		$startAgain = (bool) $this->getInput('start_again');

		// Do options actions here
		switch (true)
		{
			// When the user is generating new keys
			case $chooseGen:
				$templateVars = $this->generateNewKeys();
				break;
			// When the user is importing a new key
			case $chooseImport:
				$templateVars = $this->importKeys();
				break;
			// Remove all keys from WP
			case $startAgain:
				$templateVars = $this->eraseKeys();
				break;
			// When we have a public key but need to test it
			case !$isTested:
				$templateVars = $this->testPrivateKey();
				break;
			case $this->getInput('save_settings'):
				$templateVars = $this->saveSettings();
				break;
			default:
				$templateVars = array();
		}

		// Set up default vars passed to template
		$this->templateVars = array_merge(
			array(
				'pubKey' => $pubKey,
				'chooseImport' => $chooseImport,
				'chooseGen' => $chooseGen,
				'isTested' => $isTested,
			),
			$templateVars
		);
	}

	/**
	 * Rendering handler called to render the options screen (actions have already run by this stage)
	 */
	public function optionsScreenHandler()
	{
		if (!current_user_can('manage_options'))
		{
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		// Ensure Open SSL is supported
		$supportsSSL = extension_loaded('openssl');

		// Check some message values
		$isBadKey = $isWrongKey = $isNoSaveConfirm = false;
		if (!$this->templateVars['isTested'])
		{
			if ($error = $this->getInput('error'))
			{
				$isBadKey = ($error == self::KEY_BAD_KEY);
				$isWrongKey = ($error == self::KEY_WRONG_KEY);
				$isNoSaveConfirm = ($error == self::KEY_NO_SAVE_CONFIRM);
			}
		}

		$this->renderTemplate(
			$supportsSSL ? 'options' : 'options-nossl',
			array_merge(
				$this->templateVars,
				array(
					'isBadKey' => $isBadKey,
					'isWrongKey' => $isWrongKey,
					'isNoSaveConfirm' => $isNoSaveConfirm,
				)
			)
		);
	}
	
	public function loginActionHandler()
	{
		if ($_POST)
		{
			$privKey = $this->getInput('login') ?
				$this->getInput('private_key') :
				null;
			$this->setPrivateKeyCookie($privKey);
			wp_redirect('edit-comments.php?page=' . self::PAGE_LOGIN);
			exit();
		}
	}

	public function loginScreenHandler()
	{
		$this->renderTemplate('login', array('privKey' => $this->getPrivateKey()));
	}

	public function searchScreenHandler()
	{
		if (!current_user_can('moderate_comments'))
		{
			wp_die(__('You do not have sufficient permissions to access this page.'));
		}

		$this->renderTemplate('search');
	}

	/**
	 * An action method to handle the generation of pub/priv keys
	 * 
	 * @return array Variables to pass to the template
	 */
	protected function generateNewKeys()
	{
		// Include the library we need
		require_once $this->root . '/lib/EncDec.php';

		// Try to get the new privkey from a cookie, in case the user has already generated but not ticked
		// the agreement box
		$newPrivKey = $_COOKIE[ self::COOKIE_NEW_PRIV_KEY ];

		$EncDec = new EncDec();

		// Firstly, just store the private key in a cookie, so we can later ask the user if they
		// have saved the key to their computer
		if ( !$newPrivKey && $_GET )
		{
			$EncDec->createNewKeys();
			$newPrivKey = $EncDec->getPrivateKey();
			setcookie(
				self::COOKIE_NEW_PRIV_KEY,
				$newPrivKey,
				time() + 60 * 10,
				$this->getAdminPath(),
				$_domain = null,
				$_secure = false,
				$_httponly = true
			);
		}

		// If the user has confirmed they've saved the priv key, set the pub key in the WP options database
		if ($newPrivKey && $_POST)
		{
			$append = null;

			// If the user has confirmed they've saved the priv key, set the pub key in the WP
			// options database
			$error = false;
			if ($this->getInput('save_confirm'))
			{
				$ok = $EncDec->setKeysFromPrivateKey($newPrivKey);
				if ($ok)
				{
					update_option(self::OPTION_PUB_KEY, $EncDec->getPublicKey());
					// Set an old cookie to trigger browser delete mechanism, rather than unsetting it
					unset($_COOKIE[ self::COOKIE_NEW_PRIV_KEY ]);
					$this->setPrivateKeyCookie($newPrivKey);
				}
				else
				{
					$error = self::KEY_BAD_KEY;
				}
			}
			else
			{
				// User has forgotten to confirm, ask them to try again
				$error = self::KEY_NO_SAVE_CONFIRM;
				$append = '&gen_keys=1';
			}

			// Redirect after saving (303 = See Other)
			$pageKey = self::PAGE_OPTIONS;
			wp_redirect(
				'options-general.php?page=' . $pageKey . ($error ? '&error=' . $error : '') . $append,
				303
			);
			exit();
		}

		return array(
			'newPrivKey' => $newPrivKey,
		);
	}

	protected function testPrivateKey()
	{
		if ($this->getInput('test_key'))
		{
			// Include the library we need
			require_once $this->root . '/lib/EncDec.php';

			$privKey = $this->getInput('private_key');

			$EncDec = new EncDec();
			$ok = $EncDec->setKeysFromPrivateKey($privKey);

			// Ensure pub key from user input is the same as the stored version
			$error = false;
			if ($ok)
			{
				$pubKey = $EncDec->getPublicKey();
				if ($pubKey == get_option(self::OPTION_PUB_KEY))
				{
					update_option(self::OPTION_PUB_KEY_HASH, $EncDec->getPublicKeyLongHash());
				}
				else
				{
					$error = self::KEY_WRONG_KEY;
				}
			}
			else
			{
				$error = self::KEY_BAD_KEY;
			}

			// Redirect after saving (303 = See Other)
			$pageKey = self::PAGE_OPTIONS;
			wp_redirect(
				'options-general.php?page=' . $pageKey . ($error ? '&error=' . $error : ''),
				303
			);
			exit();
		}

		return array();
	}

	protected function eraseKeys()
	{
		delete_option(self::OPTION_PUB_KEY);
		delete_option(self::OPTION_PUB_KEY_HASH);

		// Redirect after saving (303 = See Other)
		wp_redirect('options-general.php?page=' . self::PAGE_OPTIONS, 303);
		exit();
	}

	protected function importKeys()
	{
		// Handle the form post here
		if ($_POST)
		{
			// Include the library we need
			require_once $this->root . '/lib/EncDec.php';

			$privKey = $this->getInput('private_key');
			
			$pageKey = self::PAGE_OPTIONS;

			$EncDec = new EncDec();
			$ok = $EncDec->setKeysFromPrivateKey($privKey);
			if ($ok)
			{
				$pubKey = $EncDec->getPublicKey();
				update_option(self::OPTION_PUB_KEY, $pubKey);
				update_option(self::OPTION_PUB_KEY_HASH, $EncDec->getPublicKeyLongHash());

				wp_redirect(
					"options-general.php?page={$pageKey}&imported_ok=1",
					303
				);
			}
			else
			{
				wp_redirect(
					"options-general.php?page={$pageKey}&import_keys=1&error=" . self::KEY_BAD_KEY,
					303
				);
			}
		}

		return array();
	}

	public function saveSettings()
	{
		$saveHashes = (bool) $this->getInput('save_avatar_hashes');
		update_option(self::OPTION_STORE_AVATAR_HASHES, $saveHashes);
		wp_redirect('options-general.php?page=' . self::PAGE_OPTIONS, 303);
	}

	/**
	 * Sets a cookie to store the private key
	 * 
	 * @todo Can't set path to $this->getAdminPath() since AJAX ops go to (non-admin) plugin path. Wonder
	 *	if we can piggy-back the AJAX op inside a admin URL? Worst-case scenario, we could set two cookies,
	 *	each with the right path restrictions.
	 * 
	 * @param string $privKey
	 */
	protected function setPrivateKeyCookie($privKey)
	{
		setcookie(
			self::COOKIE_PRIV_KEY,
			$privKey,
			$privKey ? time() + 60 * 10 : time() - 60 * 10,
			$_path = '/',
			$_domain = null,
			$_secure = false,
			$_httponly = true
		);
	}

	/**
	 * Get the path bit of the admin URL
	 */
	protected function getAdminPath()
	{
		return parse_url( admin_url(), PHP_URL_PATH );
	}
}
