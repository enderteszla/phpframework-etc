<?php define('BASE_PATH',$_SERVER['DOCUMENT_ROOT']);

define('SHELL_PATH',BASE_PATH . '/shell/');
define('CONFIG_PATH',BASE_PATH . '/config/');
define('CONTROLLER_PATH',BASE_PATH . '/controller/');
define('HELPER_PATH',BASE_PATH . '/helper/');
define('LANG_PATH',BASE_PATH . '/lang/');
define('VIEW_PATH',BASE_PATH . '/view/');

require_once SHELL_PATH . 'Common.php';
require_once SHELL_PATH . 'Config.php';
require_once SHELL_PATH . 'Input.php';
require_once SHELL_PATH . 'Lang.php';
require_once SHELL_PATH . 'Loader.php';

Loader::_getInstance();

spl_autoload_register(function($class){
	Loader::_getInstance()->load($class);
});

if(!array_key_exists('PATH_INFO',$_SERVER) || !preg_match_all(':([^/]+)/?:',$_SERVER['PATH_INFO'],$m)){
	$controller = config('controller','Default');
	$method = config('method','Default');
	$args = array();
} else {
	$m = $m[1];
	$controller = ucfirst($m[0]);
	$method = (array_key_exists(1,$m)) ? $m[1] : config('method','Default');
	$args = (array_key_exists(2,$m)) ? array_slice($m,2) : array();
}
$controllerInstance = $controller::_getInstance();

if(!is_callable(array($controllerInstance,$method))){
	include_once BASE_PATH . '/404.php';
}

$data = array('Token' => false,'User' => false,'Controller' => $controller);
/* <Data for="header"> */
if(Token::_getInstance()->_get(input('token'),'Content')->_eq()->__($data['Token'])->_checkAuthorization()){
	User::_getInstance()->_get($data['Token']['UserID'])->__($data['User']);
}
if(is_null($data['Token'])) {
	Error::_getInstance()->add('authentication',0);
}
/* </Data for="header"> */

call_user_func_array(array($controllerInstance,$method),$args);
Output::_getInstance()
	->setSource(array_merge($data,is_null($controllerInstance->__()) ? array() : $controllerInstance->__()))
	->expose(VIEW_PATH . lcfirst($controller) . "/$method.php");