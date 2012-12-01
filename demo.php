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

	protected $root;

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
	 *	See http://wordpress.stackexchange.com/questions/64973/is-it-possible-to-show-custom-comment-metadata-in-the-admin-panel
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

	public function commentColumnContentHandler($column, $comment_ID)
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
		add_options_page('Encrypt Demo Options', 'Encrypt Demo', 'manage_options', 'encdemo', array($this, 'optionsScreenHandler'));

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

	public function optionsScreenHandler()
	{
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// Ensure Open SSL is supported
		$supportsSSL = extension_loaded('openssl');

		// Read public key from WP
		$pubKey = get_option(self::OPTION_PUB_KEY);

		// Get some user input, untainting where appropriate
		$chooseImport = (bool) $this->getInput('import_keys');
		$chooseGen = (bool) $this->getInput('gen_keys');

		$params = array();

		if ( $chooseGen ) {
			$params = $this->generateNewKeys();
		}

		// Set up default vars passed to template
		$params = array_merge(
			array(
				'pubKey' => $pubKey,
				'chooseImport' => $chooseImport,
				'chooseGen' => $chooseGen,
			),
			$params
		);

		$this->renderTemplate(
			$supportsSSL ? 'options' : 'options-nossl',
			$params
		);
	}

	public function searchScreenHandler()
	{
		if ( !current_user_can( 'moderate_comments' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
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

	protected function generateNewKeys() {
		$newPubKey = "
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDNQzi5g7SqgKuio2sUOUqIqzpC
daaNoy1XT+pArm1Wy/WEcKYMplxA559ZZrm9dIAgiGc30GipYJ2azBTIplaeO8PC
u0f0YXEL1MZP53FMDW8kfoBVrgPJJpADrTC6TDcZ9ICtVpJvBpGpkXDs01/jwVCe
7uk2pVUi2JVz/2kcUQIDAQAB
-----END PUBLIC KEY-----
		";
		$newPrivKey = "
-----BEGIN PRIVATE KEY-----
MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAM1DOLmDtKqAq6Kj
axQ5SoirOkJ1po2jLVdP6kCubVbL9YRwpgymXEDnn1lmub10gCCIZzfQaKlgnZrM
FMimVp47w8K7R/RhcQvUxk/ncUwNbyR+gFWuA8kmkAOtMLpMNxn0gK1Wkm8GkamR
cOzTX+PBUJ7u6TalVSLYlXP/aRxRAgMBAAECgYAImbp0u3oEcuO+Ks6/yC7BTztf
sYJLCP1LXUPZdfWK33zoEbhDa20OIyZgHpfFwm3j7xM0GX1pK20vIUH1rlKOt4LT
JfnzgwSfKXwLphsc2vhCcu0qN3KfeRx6lvLjraEo8rHMzeyYvuhU4hgC8u0jVESK
pkGlaG/nxqtE2BqsAQJBAPCgxeWkvD3tc6S6s3W6V9e/0e6w4J9gYWPDwIPq3jbT
6/VPRKiy6amFC3q5wz4TXLcA6ej1iOZICAjbZhmlebECQQDaYBUvZ8nayPAvACG+
dloBX8Lh4r3nN+wq19muoqekUZloSGOrsQgc/tbq0Mu1u9DuWv7IxImRD7H4piYx
4dShAkEAuXkC8N5IZmdnktqBx0XJvbfSBex6Rv6QMsjI1CWuAI7aumvOHUZCivLN
BVy4HFnqRfjDU1gmnHF7F/CcwznkEQJACM9fi24QgrcgmYTT169Gqk+GuT5Akxd6
e7ABpD4DrWltWvuwqbiWrzTIzuhlj4toPnWFWewz8JpFf9aUK+cEgQJAc+vZPj8y
66+wZyqzidMFN71nQAZD/eiZzMC8hhw5Pg/iz1hy7ZThVqhfC+f/wXWeGBWv7k5s
pHiR1IsSRX2SEw==
-----END PRIVATE KEY-----
		";

		return array(
			'newPubKey' => $newPubKey,
			'newPrivKey' => $newPrivKey,
		);
	}

	protected function postHandler()
	{
		
	}
}

new EncryptDemo();

// Illustrates all actions
/*
add_action('all','hook_catchall');
function hook_catchall(&$s1 = '', &$s2 = '', &$s3 = '', &$s4 = '') {
    echo "<h1>1</h1>\n";
    print_r($s1);
    echo "<br />\n";
    echo "<h1>2</h1>\n";
    print_r($s2);
    echo "<br />\n";
    echo "<h1>3</h1>\n";    
    print_r($s3);
    echo "<br />\n";
    echo "<h1>4</h1>\n";    
    print_r($s4);
    echo "<br />\n";
    return $s1;
}
*/