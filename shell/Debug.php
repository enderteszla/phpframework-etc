<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Debug {
	use Shell;

	private $traceFlag = null;

	private function __init(){
		Config::getInstance()->load('Debug');
	}

	public function start(){
		$this->traceFlag = true;
		return $this;
	}
	public function stop(){
		$this->traceFlag = false;
		return $this;
	}
	public function status(){
		return $this->traceFlag;
	}
	public function get(){
		if(!$this->traceFlag){
			return false;
		}
		$status = $this->traceFlag;
		$this->traceFlag = false;
		$return = "";
		foreach(func_get_args() as $arg){
			$return .= var_export($arg,config('echo','Debug')) . config('lf','Debug');
		}
		$this->traceFlag = $status;
		if(config('die','Debug')){
			die();
		}
		return $return;
	}
}