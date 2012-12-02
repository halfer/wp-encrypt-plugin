<?php
/*
Plugin Name: Encrypt Demo
Plugin URI: http://blog.jondh.me.uk/encrypt-demo
Description: Encrypt email/IP of comments using PPK
Version: 0.1
Author: Jon Hinks
Author URI: http://blog.jondh.me.uk/
License: GPL2
*/

class EncryptDemo
{
	const OPTION_PUB_KEY = 'encdemo_pub_key';
	const COOKIE_NEW_PRIV_KEY = 'wp-encrypt-plugin_new-priv-key';
	const COOKIE_PRIV_KEY = 'wp-encrypt-plugin_priv-key';

	// Stores the plugin folder path
	protected $root;

	// Used to pass template rendering values around this object
	protected $templateVars = array();

	public function __construct()
	{
		// Set up root folder
		$this->root = dirname(__FILE__);

		// Initialisation
		$this->initDecryptedEmail();
		$this->initDecryptedIP();
		$this->initNewCommentsAdmin();
		$this->initMetaAction();
		$this->initScreens();
		$this->initCssQueueing();

		// Handle POST (we can probably skip a few inits above, since we'll redirect anyway after the op)
		if ($_POST)
		{
			$this->postHandler();
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
	}

	protected function initCssQueueing()
	{
		// See http://codex.wordpress.org/Plugin_API/Action_Reference/admin_print_styles
		// for the reason why we're not using admin_print_styles here
		add_action('admin_enqueue_scripts', array($this, 'queueCss'));
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

	public function commentColumnContentHandler($column, $commentId)
	{
		if ( 'encrypt' == $column ) {
			echo '<img src="/wp/wp-content/plugins/encrypt-demo/lock.png" /> Yes';
		}		
	}

	/**
	 * Renders the 'Ban IP X' by decoding the IP in the comment
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

	public function queueCss($hook)
	{
		if ($hook == 'settings_page_encdemo')
		{
			// @todo Fix the absolute pathname here
			wp_register_style(
				'encdemo_css',
				'/wp-content/plugins/encrypt-demo/styles/main.css'
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

		// Get some user input, untainting where appropriate
		$chooseImport = (bool) $this->getInput('import_keys');
		$chooseGen = (bool) $this->getInput('gen_keys');

		// Do options actions here
		if ( $chooseGen )
		{
			$templateVars = $this->generateNewKeys();
		}
		else
		{
			$templateVars = array();			
		}

		// Set up default vars passed to template
		$this->templateVars = array_merge(
			array(
				'pubKey' => $pubKey,
				'chooseImport' => $chooseImport,
				'chooseGen' => $chooseGen,
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

		$this->renderTemplate(
			$supportsSSL ? 'options' : 'options-nossl',
			$this->templateVars
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

	protected function renderTemplate($template, array $params = array())
	{
		extract($params);
		require_once "{$this->root}/templates/{$template}.php";
	}

	protected function getInput($key)
	{
		return isset($_REQUEST[$key]) ? $_REQUEST[$key] : null;
	}

	protected function generateNewKeys()
	{
		
		// Include the library we need
		require_once $this->root . '/lib/EncDec.php';

		// Try to get the new privkey from a cookie, in case the user has already generated but not ticked
		// the agreement box
		$newPrivKey = $_COOKIE[ self::COOKIE_NEW_PRIV_KEY ];

		$EncDec = new EncDec();

		if ( !$newPrivKey )
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

		// Set the permanent WP option if the user has confirmed they've saved it
		if ($this->getInput('save_confirm') && $newPrivKey)
		{
			set_option(self::OPTION_PUB_KEY, $EncDec->getPublicKey());
			unset($_COOKIE[ self::COOKIE_NEW_PRIV_KEY ]);
			set_cookie(
				self::COOKIE_NEW_PRIV_KEY,
				$newPrivKey,
				time() + 60 * 10,
				$_path = '/wp/wp-admin',
				$_domain = null,
				$_secure = false,
				$_httponly = true
			);
		}

		return array(
			'newPrivKey' => $newPrivKey,
		);
	}

	protected function postHandler()
	{
		
	}
}

new EncryptDemo();
