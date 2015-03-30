<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Debug {
	use Shell;

	private $traceFlag = null;

	private function __init(){
		Config::_getInstance()->load('Debug');
	}

	public function start(){
		$this->traceFlag = true;
		if(config('echo','Debug')){
			echo config('initialSequence','Debug');
		}
		return $this;
	}
	public function stop(){
		$this->traceFlag = false;
		if(config('echo','Debug')){
			echo config('terminalSequence','Debug');
		}
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
			if(config('echo','Debug')){
				print_r($arg);
				linefeed();
			} else {
				$return .= print_r($arg,true);
			}
		}
		$this->traceFlag = $status;
		if(config('die','Debug')){
			die();
		}
		return $return;
	}
}