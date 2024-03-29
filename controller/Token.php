<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Token extends Controller {
	/**
	 * @return bool
	 */
	public function _checkAuthorization(){
		return is_assoc($this->_result) && !is_null($this->_result['UserID']) && $this->_result['Type'] == 'session';
	}

	/**
	 * @return bool
	 */
	public function _checkAdmin(){
		return $this->_checkAuthorization() && User::_getInstance()->_get($this->_result['UserID'],null,'Role')->__()['RoleName'] == 'admin';
	}

	/**
	 * @return string
	 */
	public function _generate(){
		do {
			$token = md5(uniqid(mt_rand(), true));
		} while(!is_null($this->_get($token,'Content')->_result));
		return $token;
	}

	/**
	 * @return $this
	 */
	public function _refresh(){
		if(!is_assoc($this->_result)){
			return $this;
		}
		DB::_getInstance()->query("UPDATE `Token` SET `Created` = NULL WHERE `ID` = {$this->_result['ID']};");
		return $this;
	}

	/**
	 * @return $this
	 */
	public function cleanExpired(){
		if(!IS_CLI){
			return error404();
		}
		Config::_getInstance()->load('Token');
		$eI = config('expireInterval','Token');
		DB::_getInstance()->query("DELETE FROM `Token` WHERE `Created` < NOW() - INTERVAL $eI AND `ID` > 1");
		return $this;
	}
}