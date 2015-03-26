<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Token {
	use Controller;

	public function _checkAuthorization(){
		return is_assoc($this->_result) && !is_null($this->_result['UserID']) && $this->_result['Type'] == 'session';
	}
	public function _checkAdmin(){
		return $this->_checkAuthorization() && User::_getInstance()->_get($this->_result['UserID'],null,'Role')->__()['RoleName'] == 'admin';
	}
	public function _generate(){
		do {
			$token = md5(uniqid(mt_rand(), true));
		} while(!is_null($this->_get($token,'Content')->_result));
		return $token;
	}
}