<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

$Lang['error']['activation'] = array(
	'name'  => "Activation Error",
	0       => "Token doesn't exist"
);
$Lang['error']['authentication'] = array(
	'name'  => "Authentication Error",
	0       => "Token doesn't exist",
	1       => "User must be authorized to perform this action",
	2       => "Session already closed",
	3       => "User not found",
	4       => "Wrong email or password",
);
$Lang['error']['connection'] = array(
	'name'  => "Database Connection Error"
);
$Lang['error']['drop'] = array(
	'name'  => "Data Deletion from Database Error"
);
$Lang['error']['email'] = array(
	'name'  => "Email Module Error",
	0       => "Template doesn't exist"
);
$Lang['error']['insert/update'] = array(
	'name'  => "Insert/Update Error"
);
$Lang['error']['output'] = array(
	'name'  => "Output Error",
	0       => "Non-parseable source",
	1       => "View doesn't exist"
);
$Lang['error']['password restoration'] = array(
	'name'  => "Password Restoration Error",
	0       => "Token doesn't exist",
	1       => "User not found"
);
$Lang['error']['pbkdf2'] = array(
	'name'  => "PBKDF2 Error",
	0       => "Invalid hash algorithm",
	1       => "Invalid parameters"
);
$Lang['error']['registration'] = array(
	'name'  => "Registration Error",
	0       => "User with the specified e-mail already exists"
);
$Lang['error']['select'] = array(
	'name'  => "Select Error",
	0       => "Object '%1\$s' with ID = %2\$s doesn't exist"
);
$Lang['error']['set'] = array(
	'name'  => "Set Error"
);
$Lang['error']['validation'] = array(
	'name'  => "Validation Error",
	0       => "No validation rule for '%1\$s' class",
	1       => "Array value for field '%1\$s' has no valid elements",
	2       => "Field '%1\$s' has invalid value",
	3       => "Empty request",
	4       => "Field '%1\$s' required",
	5       => "ID has invalid value",
	6       => "Invalid locale"
);