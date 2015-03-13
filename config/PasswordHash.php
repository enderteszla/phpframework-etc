<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

// These constants may be changed without breaking existing hashes.
$Config["PBKDF2_HASH_ALGORITHM"] = "sha256";
$Config["PBKDF2_ITERATIONS"] = 1000;
$Config["PBKDF2_SALT_BYTE_SIZE"] = 24;
$Config["PBKDF2_HASH_BYTE_SIZE"] = 24;

$Config["HASH_SECTIONS"] = 4;
$Config["HASH_ALGORITHM_INDEX"] = 0;
$Config["HASH_ITERATION_INDEX"] = 1;
$Config["HASH_SALT_INDEX"] = 2;
$Config["HASH_PBKDF2_INDEX"] = 3;