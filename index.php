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
$controllerInstance = $controller::_getInstance();

if(!method_exists($controllerInstance,$method)){
	include_once BASE_PATH . '/404.php';
}

$data = array('token' => false,'_user' => false);
/* <Data for="header"> */
if(Token::_getInstance()->_get(input('token'),'Content')->_eq()->__($data['token'])->_checkAuthorization()){
	User::_getInstance()->_get($data['token']['UserID'])->__($data['_user']);
}
if(is_null($data['token'])) {
	Error::_getInstance()->add('authentication',0);
}
/* </Data for="header"> */

call_user_func_array(array($controllerInstance,$method),$args);
Output::_getInstance()
	->setSource(array_merge($data,is_null($controllerInstance->__()) ? array() : $controllerInstance->__()))
	->expose(VIEW_PATH . strtolower($controller) . "/$method.php");