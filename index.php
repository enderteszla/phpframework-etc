<?php switch(PHP_SAPI){
	case 'cli':
		define('BASE_PATH',__DIR__);
		define('IS_CLI',true);
		$_SERVER['PATH_INFO'] = '/' . implode('/',array_slice($argv,1));
		break;
	default:
		define('BASE_PATH',$_SERVER['DOCUMENT_ROOT']);
		define('IS_CLI',false);
}

define('SHELL_PATH',BASE_PATH . '/shell/');
define('CONFIG_PATH',BASE_PATH . '/config/');
define('CONTROLLER_PATH',BASE_PATH . '/controller/');
define('HELPER_PATH',BASE_PATH . '/helper/');
define('LANG_PATH',BASE_PATH . '/lang/');
define('VIEW_PATH',BASE_PATH . '/view/');

function error404(){ include_once BASE_PATH . '/404.php'; return $this; }

require_once SHELL_PATH . 'Common.php';
require_once SHELL_PATH . 'Config.php';
require_once SHELL_PATH . 'Input.php';
require_once SHELL_PATH . 'Lang.php';
require_once SHELL_PATH . 'Loader.php';

Loader::_getInstance();

spl_autoload_register(function($class){
	Loader::_getInstance()->load($class);
});

if(
	(!array_key_exists('ORIG_PATH_INFO',$_SERVER) || !preg_match_all(':([^/]+)/?:',$_SERVER['ORIG_PATH_INFO'],$m)) &&
	(!array_key_exists('PATH_INFO',$_SERVER) || !preg_match_all(':([^/]+)/?:',$_SERVER['PATH_INFO'],$m))
){
	$controller = config('controller','Default');
	$method = config('method','Default');
	$args = array();
} else {
	$m = $m[1];
	$controller = ucfirst($m[0]);
	$method = (array_key_exists(1,$m)) ? $m[1] : config('method','Default');
	$args = (array_key_exists(2,$m)) ? array_slice($m,2) : array();
}

$data = array('Token' => false,'User' => false,'Controller' => $controller);
/* <Data for="header"> */
if(Token::_getInstance()->_get(input('token'),'Content')->_eq()->__($data['Token'])->_checkAuthorization()){
	User::_getInstance()->_get($data['Token']['UserID'])->__($data['User']);
}
if(is_null($data['Token']) && is_null($data['Token'] = Token::_getInstance()->_get(config('token','Input'),'Content')->_eq()->__())) {
	Error::_getInstance()->add('authentication',0);
} else {
	Token::_getInstance()->_refresh();
}
/* </Data for="header"> */

$controllerInstance = $controller::_getInstance();
if(!is_callable(array($controllerInstance,$method))){
	error404();
}
call_user_func_array(array($controllerInstance,$method),$args);
$result = $controllerInstance->__();

Output::_getInstance()
	->setSource(array_merge($data,!is_assoc($result) ? array() : $result))
	->expose(VIEW_PATH . lcfirst($controller) . "/$method.php");