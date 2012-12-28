<?php

class AssymetricEncryptor {

	protected $useBase64;
	protected $pubKey;
	protected $privKey;

	public function __construct($useBase64 = true)
	{
		$this->useBase64 = $useBase64;
	}

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

	public function getPublicKeyLongHash()
	{
		// Shouldn't I trim first, then take the hash?
		return trim(sha1($this->getPublicKey()));
	}

	public function getPublicKeyShortHash()
	{
		return substr($this->getPublicKeyLongHash(), 0, 12);
	}

	public function getPrivateKey() {
		return $this->privKey;
	}

	public function encrypt($data) {

		if (!$this->pubKey)
		{
			throw new Exception('A public key must be set prior to encryption');
		}

		$encrypted = null;
		openssl_public_encrypt($data, $encrypted, $this->pubKey);
		if ($this->useBase64)
		{
			$encrypted = base64_encode($encrypted);
		}

		return $encrypted;
	}

	public function decrypt($encryptedData) {

		if (!$this->privKey)
		{
			throw new Exception('A private key must be set prior to decryption');
		}

		if ($this->useBase64)
		{
			$encryptedData = base64_decode($encryptedData);
		}

		$decrypted = null;
		openssl_private_decrypt($encryptedData, $decrypted, $this->privKey);

		return $decrypted;
	}
}