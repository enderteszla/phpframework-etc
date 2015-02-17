<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Token {
	use Controller;

	public function checkAuthorization(){
		if(is_null($this->result[0]['UserID']) || $this->result[0]['Type'] != 'session'){
			return $this->addError('authentication',1);
		}
		return $this;
	}
	public function generate(){
		do {
			$token = md5(uniqid(mt_rand(), true));
		} while(!is_null($this->get($token,'Content')->result));
		return $token;
	}
}