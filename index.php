<?php define('BASE_PATH',$_SERVER['DOCUMENT_ROOT']);

define('SHELL_PATH',BASE_PATH . '/shell/');
define('CONFIG_PATH',BASE_PATH . '/config/');
define('CONTROLLER_PATH',BASE_PATH . '/controller/');
define('HELPER_PATH',BASE_PATH . '/helper/');
define('LANG_PATH',BASE_PATH . '/lang/');
define('VIEW_PATH',BASE_PATH . '/view/');

require_once SHELL_PATH . 'Common.php';
require_once SHELL_PATH . 'Config.php';
require_once SHELL_PATH . 'Debug.php';
require_once SHELL_PATH . 'Input.php';
require_once SHELL_PATH . 'Lang.php';
require_once SHELL_PATH . 'Output.php';
require_once HELPER_PATH . 'Array.php';
require_once HELPER_PATH . 'Config.php';
require_once HELPER_PATH . 'Debug.php';
require_once HELPER_PATH . 'Input.php';
require_once HELPER_PATH . 'Lang.php';
require_once CONTROLLER_PATH . 'Image.php';
require_once CONTROLLER_PATH . 'Token.php';
require_once CONTROLLER_PATH . 'User.php';

if(!array_key_exists('PATH_INFO',$_SERVER) || !preg_match_all(':([^/]+)/?:',$_SERVER['PATH_INFO'],$m)){
	$controller = config('controller','Default');
	$method = config('method','Default');
	$args = array();
} else {
	$m = $m[1];
	$controller = ucfirst(strtolower($m[0]));
	$method = (array_key_exists(1,$m)) ? $m[1] : config('method','Default');
	$args = (array_key_exists(2,$m)) ? array_slice($m,2) : array();
}
if(!is_file(CONTROLLER_PATH . $controller . '.php')){
	include_once BASE_PATH . '/404.php';
}
require_once CONTROLLER_PATH . $controller . '.php';
$controllerInstance = $controller::getInstance();

if(!method_exists($controllerInstance,$method)){
	include_once BASE_PATH . '/404.php';
}

$data = array();
/* <Data for="header"> */
if(!empty(Token::getInstance()->_get(input('token'),'Content')->errors())) {
	$controllerInstance->addError('authentication',0);
}
if(Token::getInstance()->checkAuthorization()->errors()){
	Token::getInstance()->flushErrors();
} else {
	User::getInstance()->_get(Token::getInstance()->getResult()[0]['UserID'])->putResult($data['_user']);
}
/* </Data for="header"> */

call_user_func_array(array($controllerInstance,$method),$args);
Output::getInstance()
	->addErrors($controllerInstance->errors())
	->setSource(array_merge($data,$controllerInstance->getResult()))
	->expose(VIEW_PATH . strtolower($controller) . "/$method.php");