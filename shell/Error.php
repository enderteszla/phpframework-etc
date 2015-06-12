<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Error extends Singleton {
	/**
	 * @var array
	 */
	private $errors = null;

	protected function __init(){
		$this->errors = array();
	}

	/**
	 * @param string $key
	 * @param string $code
	 * @param array $details
	 * @return $this
	 */
	public function add($key,$code,$details = array()){
		$error = ($this->errors[] = Lang::_getInstance()->getError($key,$code,$details));
		switch($this->_('verbosity')){
			case 'die':
				die($error);
			case 'echo':
				echo $error;
			default:
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	public function flush(){
		$this->errors = array();
		return $this;
	}

	/**
	 * @return array
	 */
	public function get(){
		return $this->errors;
	}

	/**
	 * @return int
	 */
	public function count(){
		return count($this->errors);
	}
}