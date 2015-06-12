<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Debug extends Singleton {
	/**
	 * @var bool
	 */
	private $traceFlag = null;

	protected function __init(){
		Config::_getInstance()->load('Debug');
	}

	/**
	 * @return $this
	 */
	public function start(){
		$this->traceFlag = true;
		if(config('echo','Debug')){
			echo config('initialSequence','Debug');
		}
		return $this;
	}

	/**
	 * @return $this
	 */
	public function stop(){
		$this->traceFlag = false;
		if(config('echo','Debug')){
			echo config('terminalSequence','Debug');
		}
		return $this;
	}

	/**
	 * @return bool
	 */
	public function status(){
		return $this->traceFlag;
	}

	/**
	 * @return bool|string
	 */
	public function get(){
		if(!$this->traceFlag){
			return false;
		}
		$status = $this->traceFlag;
		$this->traceFlag = false;
		$function = config('function','Debug');
		$return = "";
		foreach(func_get_args() as $arg){
			if(config('echo','Debug')){
				$function($arg);
				linefeed();
			} else {
				$return .= $function($arg,true);
			}
		}
		$this->traceFlag = $status;
		if(config('die','Debug')){
			die();
		}
		return $return;
	}
}