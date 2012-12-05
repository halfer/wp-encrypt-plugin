<?php

class EncDec {

	protected $pubKey;
	protected $privKey;

	/**
	 * Creates a public and private key for subsequent use
	 */
	public function createNewKeys() {
		/* Create the private and public key */
		$res = openssl_pkey_new();

		/* Extract the private key from $res to $privKey */
		openssl_pkey_export($res, $this->privKey);

		/* Extract the public key from $res to $pubKey */
		$pubKey = openssl_pkey_get_details($res);
		$this->pubKey = $pubKey["key"];
	}

	public function setKeysFromPrivateKey($privateKey)
	{
		$res = openssl_pkey_get_private($privateKey);
		$ok = ($res !== false);

		if ($ok)
		{
			$pubKey = openssl_pkey_get_details($res);
			$this->privKey = $privateKey;
			$this->pubKey = $pubKey['key'];
		}

		return $ok;
	}

	public function setPublicKey($pubKey)
	{
		$this->pubKey = $pubKey;
	}

	public function getPublicKey() {
		return $this->pubKey;
	}

	public function getPrivateKey() {
		return $this->privKey;
	}

	public function encrypt($data) {
		$encrypted = null;
		openssl_public_encrypt($data, $encrypted, $this->pubKey);

		return $encrypted;
	}

	public function decrypt($encryptedData) {
		$decrypted = null;
		openssl_private_decrypt($encryptedData, $decrypted, $this->privKey);

		return $decrypted;
	}
}