<?php

class EncryptDemo extends EncryptTemplate
{
	// Paths
	const PATH_PLUGIN_NAME = 'wp-encrypt-plugin';

	// Stores values in WP options
	const OPTION_PUB_KEY = 'encdemo_pub_key';
	const OPTION_PUB_KEY_HASH = 'encdemo_pub_key_hash';

	// Stores values in user cookies
	const COOKIE_NEW_PRIV_KEY = 'wp-encrypt-plugin_new-priv-key';
	const COOKIE_PRIV_KEY = 'wp-encrypt-plugin_priv-key';

	// Stores values in comment metadata
	const META_ENCRYPTED = 'encdemo_encrypt';
	const META_PUB_KEY_HASH = 'encdemo_pub_key_hash';
	const META_VERSION = 'encdemo_version';

	const KEY_BAD_KEY = 'bad_priv_key';
	const KEY_WRONG_KEY = 'wrong_priv_key';
	const KEY_NO_SAVE_CONFIRM = 'no_key_save_confirm';

	// Used to pass template rendering values around this object
	protected $templateVars = array();

	public function preExecute()
	{
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
		$privKeySet = (bool) $_COOKIE[self::COOKIE_PRIV_KEY];

		$WpAdminBar->add_menu(
			array(
				'id' => 'encdemo_key_status',
				'title' => $privKeySet? 'Private key set' : 'Private key unknown',
				'href' => false,
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
		return 'hello@example.com';	
	}

	public function getDecryptedIP()
	{
		return '127.0.0.1';
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
	 * @todo Fix dummy output
	 * @todo Remove hardwired path, get plugin http path from wp
	 * 
	 * @param string $column
	 * @param integer $commentId
	 */
	public function commentColumnContentHandler($column, $commentId)
	{
		if ( 'encrypt' == $column ) {
			echo '<img src="/wp/wp-content/plugins/' . self::PATH_PLUGIN_NAME . '/lock.png" /> Yes';
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

		// @todo Add a proper link to this
		$actions['ip_ban'] = '<a href="#">Ban IP X</a>';

		return $actions;
	}

	public function screensHandler()
	{
		// Add settings menu item
		$hookSuffix = add_options_page(
			'Encrypt Demo Options',
			'Encrypt Demo',
			'manage_options',
			'encdemo',
			array($this, 'optionsScreenHandler')
		);

		// Add an actions handler for this page
		add_action('load-' . $hookSuffix, array($this, 'optionsActionHandler'));

		// Add submenu page
		add_submenu_page(
			'edit-comments.php',
			'Search encrypted email/IP',
			'Search encrypted',
			'moderate_comments',
			'search-enc',
			array($this, 'searchScreenHandler')
		);
	}

	/**
	 * Registers CSS styles for our pages only
	 * 
	 * @todo Remove hardwired path, get plugin http path from wp
	 * 
	 * @param string $hook
	 */
	public function queueCss($hook)
	{
		if ($hook == 'settings_page_encdemo')
		{
			// @todo Fix the absolute pathname here
			wp_register_style(
				'encdemo_css',
				'/wp-content/plugins/' . self::PATH_PLUGIN_NAME . '/styles/main.css'
			);
			wp_enqueue_style('encdemo_css');
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
				break;
			// Remove all keys from WP
			case $startAgain:
				$templateVars = $this->eraseKeys();
				break;
			// When we have a public key but need to test it
			case !$isTested:
				$templateVars = $this->testPrivateKey();
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
	 * @todo Fix up the cookie relative http paths, get this from wp
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
				$_path = '/wp/wp-admin',
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
					unset($_COOKIE[ self::COOKIE_NEW_PRIV_KEY ]);
					setcookie(
						self::COOKIE_PRIV_KEY,
						$newPrivKey,
						time() + 60 * 10,
						$_path = '/wp/wp-admin',
						$_domain = null,
						$_secure = false,
						$_httponly = true
					);
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
			wp_redirect(
				'options-general.php?page=encdemo' . ($error ? '&error=' . $error : '') . $append,
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
		// Include the library we need
		require_once $this->root . '/lib/EncDec.php';

		if ($this->getInput('test_key'))
		{
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
					update_option(self::OPTION_PUB_KEY_HASH, sha1($pubKey));
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
			wp_redirect(
				'options-general.php?page=encdemo' . ($error ? '&error=' . $error : ''),
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
		wp_redirect('options-general.php?page=encdemo', 303);
		exit();
	}
}
