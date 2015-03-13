<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

$Lang['activation'] = array(
	'name'  => "Activation Error",
	0       => "Token doesn't exist"
);
$Lang['authentication'] = array(
	'name'  => "Authentication Error",
	0       => "Token doesn't exist",
	1       => "User must be authorized to perform this action",
	2       => "Session already closed",
	3       => "User not found",
	4       => "Wrong email or password",
);
$Lang['connection'] = array(
	'name'  => "Database Connection Error"
);
$Lang['drop'] = array(
	'name'  => "Data Deletion from Database Error"
);
$Lang['email'] = array(
	'name'  => "Email Module Error",
	0       => "Template doesn't exist"
);
$Lang['image'] = array(
	'name'  => "Image processing library error",
	0       => "File not uploaded, please retry.",
	1       => "Uploaded file is not a valid jpeg/jpg/gif/png image, please retry."
);
$Lang['insert/update'] = array(
	'name'  => "Insert/Update Error"
);
$Lang['output'] = array(
	'name'  => "Output Error",
	0       => "Non-parseable source",
	1       => "View doesn't exist"
);
$Lang['password restoration'] = array(
	'name'  => "Password Restoration Error",
	0       => "Token doesn't exist",
	1       => "User not found"
);
$Lang['pbkdf2'] = array(
	'name'  => "PBKDF2 Error",
	0       => "Invalid hash algorithm",
	1       => "Invalid parameters"
);
$Lang['registration'] = array(
	'name'  => "Registration Error",
	0       => "User with the specified e-mail already exists"
);
$Lang['select'] = array(
	'name'  => "Select Error",
	0       => "Object '%1\$s' with ID = %2\$s doesn't exist"
);
$Lang['set'] = array(
	'name'  => "Set Error"
);
$Lang['validation'] = array(
	'name'  => "Validation Error",
	0       => "No validation rule for '%1\$s' class",
	1       => "Array value for field '%1\$s' has no valid elements",
	2       => "Field '%1\$s' has invalid value %2\$s",
	3       => "Empty request",
	4       => "Field '%1\$s' required",
	5       => "ID has invalid value %1\$s",
	6       => "Invalid locale"
);