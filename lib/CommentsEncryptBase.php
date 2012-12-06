<?php

class CommentsEncryptBase extends TemplateSystem
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
	const META_ENCRYPTED = 'commentenc_encrypt';
	const META_PUB_KEY_HASH = 'commentenc_pub_key_hash';
	const META_VERSION = 'commentenc_version';

	const KEY_BAD_KEY = 'bad_priv_key';
	const KEY_WRONG_KEY = 'wrong_priv_key';
	const KEY_NO_SAVE_CONFIRM = 'no_key_save_confirm';

	const PAGE_OPTIONS = 'options-enc';
	const PAGE_LOGIN = 'login-enc';
	const PAGE_SEARCH = 'search-enc';	
}