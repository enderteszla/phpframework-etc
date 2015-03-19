<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Error {
	use Singleton;

	private $errors = null;

	private function __init(){
		$this->errors = array();
	}

	public function add($key,$code,$details = array()){
		$this->errors[] = Lang::_getInstance()->getError($key,$code,$details);
		return $this;
	}
	public function flush(){
		$this->errors = array();
		return $this;
	}
	public function get(){
		return $this->errors;
	}
	public function count(){
		return count($this->errors);
	}
}