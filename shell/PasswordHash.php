<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

/*
 * Password Hashing With PBKDF2 (http://crackstation.net/hashing-security.htm).
 * Copyright (c) 2013, Taylor Hornby
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation
 * and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

class PasswordHash extends Shell {
	/**
	 * @var string
	 */
	private $PBKDF2_HASH_ALGORITHM = null;
	/**
	 * @var int
	 */
	private $PBKDF2_ITERATIONS = null;
	/**
	 * @var int
	 */
	private $PBKDF2_SALT_BYTE_SIZE = null;
	/**
	 * @var int
	 */
	private $PBKDF2_HASH_BYTE_SIZE = null;
	/**
	 * @var int
	 */
	private $HASH_SECTIONS = null;
	/**
	 * @var int
	 */
	private $HASH_ALGORITHM_INDEX = null;
	/**
	 * @var int
	 */
	private $HASH_ITERATION_INDEX = null;
	/**
	 * @var int
	 */
	private $HASH_SALT_INDEX = null;
	/**
	 * @var int
	 */
	private $HASH_PBKDF2_INDEX = null;

	protected function __init(){
		Config::_getInstance()->load('PasswordHash');
		foreach(config('PasswordHash') as $key => $value){
			$this->$key = $value;
		}
	}

	/**
	 * @param string $password
	 * @return string
	 */
	public function create_hash($password){
		// format: algorithm:iterations:salt:hash
		$salt = base64_encode(mcrypt_create_iv($this->PBKDF2_SALT_BYTE_SIZE, MCRYPT_DEV_URANDOM));
		return $this->PBKDF2_HASH_ALGORITHM . ":" . $this->PBKDF2_ITERATIONS . ":" .  $salt . ":" .
		base64_encode($this->pbkdf2(
			$this->PBKDF2_HASH_ALGORITHM,
			$password,
			$salt,
			$this->PBKDF2_ITERATIONS,
			$this->PBKDF2_HASH_BYTE_SIZE,
			true
		));
	}

	/**
	 * @param string $password
	 * @param string $correct_hash
	 * @return bool
	 */
	public function validate_password($password, $correct_hash){
		$params = explode(":", $correct_hash);
		if(count($params) < $this->HASH_SECTIONS)
			return false;
		$pbkdf2 = base64_decode($params[$this->HASH_PBKDF2_INDEX]);
		return $this->slow_equals(
			$pbkdf2,
			$this->pbkdf2(
				$params[$this->HASH_ALGORITHM_INDEX],
				$password,
				$params[$this->HASH_SALT_INDEX],
				(int)$params[$this->HASH_ITERATION_INDEX],
				strlen($pbkdf2),
				true
			)
		);
	}

	/**
	 * Compares two strings $a and $b in length-constant time.
	 * @param $a
	 * @param $b
	 * @return bool
	 */
	private function slow_equals($a, $b){
		$diff = strlen($a) ^ strlen($b);
		for($i = 0; $i < strlen($a) && $i < strlen($b); $i++)
		{
			$diff |= ord($a[$i]) ^ ord($b[$i]);
		}
		return $diff === 0;
	}

	/**
	 * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
	 * $algorithm - The hash algorithm to use. Recommended: SHA256
	 * $password - The password.
	 * $salt - A salt that is unique to the password.
	 * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
	 * $key_length - The length of the derived key in bytes.
	 * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
	 * Returns: A $key_length-byte key derived from the password and salt.
	 *
	 * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
	 *
	 * This implementation of PBKDF2 was originally created by https://defuse.ca
	 * With improvements by http://www.variations-of-shadow.com
	 * @param string $algorithm
	 * @param string $password
	 * @param string $salt
	 * @param int $count
	 * @param int $key_length
	 * @param bool $raw_output
	 * @return mixed|string
	 */
	private function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false){
		$algorithm = strtolower($algorithm);
		if(!in_array($algorithm, hash_algos(), true))
			$this->addError('pbkdf2',0);
		if($count <= 0 || $key_length <= 0)
			$this->addError('pbkdf2',1);

		if (function_exists("hash_pbkdf2")) {
			// The output length is in NIBBLES (4-bits) if $raw_output is false!
			if (!$raw_output) {
				$key_length = $key_length * 2;
			}
			return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output);
		}

		$hash_length = strlen(hash($algorithm, "", true));
		$block_count = ceil($key_length / $hash_length);

		$output = "";
		for($i = 1; $i <= $block_count; $i++) {
			// $i encoded as 4 bytes, big endian.
			$last = $salt . pack("N", $i);
			// first iteration
			$last = $xorSum = hash_hmac($algorithm, $last, $password, true);
			// perform the other $count - 1 iterations
			for ($j = 1; $j < $count; $j++) {
				$xorSum ^= ($last = hash_hmac($algorithm, $last, $password, true));
			}
			$output .= $xorSum;
		}

		if($raw_output)
			return substr($output, 0, $key_length);
		else
			return bin2hex(substr($output, 0, $key_length));
	}
}