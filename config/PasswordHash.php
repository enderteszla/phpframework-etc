<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

// These constants may be changed without breaking existing hashes.
$PasswordHash["PBKDF2_HASH_ALGORITHM"] = "sha256";
$PasswordHash["PBKDF2_ITERATIONS"] = 1000;
$PasswordHash["PBKDF2_SALT_BYTE_SIZE"] = 24;
$PasswordHash["PBKDF2_HASH_BYTE_SIZE"] = 24;

$PasswordHash["HASH_SECTIONS"] = 4;
$PasswordHash["HASH_ALGORITHM_INDEX"] = 0;
$PasswordHash["HASH_ITERATION_INDEX"] = 1;
$PasswordHash["HASH_SALT_INDEX"] = 2;
$PasswordHash["HASH_PBKDF2_INDEX"] = 3;