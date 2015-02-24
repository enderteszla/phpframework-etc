<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Output {
	use Shell;

	private $config = null;
	private $dataType = null;
	private $source = null;

	private function __init(){
		$Output = array();
		include_once CONFIG_PATH . 'Output.php';
		$this->config = $Output;
		$this->dataType = $this->config['allowedDataTypes'][0];
	}

	public function setDataType($dataType){
		if(in_array($dataType,$this->config['allowedDataTypes'])){
			$this->dataType = $dataType;
		}
		return $this;
	}
	public function setSource($source){
		$this->source = $source;
		return $this;
	}
	public function expose(){
		if(!method_exists($this,$this->dataType)){
			include BASE_PATH . '/404.php';
		}
		call_user_func_array(array($this,$this->dataType),func_get_args());
		return $this;
	}

	private function view($view){
		if($this->_errorsNumber || !is_array($this->source) || !is_file($view)){
			include BASE_PATH . '/404.php';
		}
		foreach($this->source as $key => $value){
			$$key = $value;
		}
		include $view;
		return $this;
	}
	private function json(){
		if($this->_errorsNumber > 0){
			$data = array(
				'status' => 'Fail',
				'data' => $this->errors()
			);
		} else {
			$data = array(
				'status' => 'OK',
				'data' => $this->source
			);
		}
		echo json_encode($data,JSON_UNESCAPED_UNICODE);
		return $this;
	}
	private function viewInJson($view){
		ob_start();
		return $this->view($view)->setSource(ob_get_clean())->json();
	}
	private function viewReturned($view){
		if($this->_errorsNumber){
			return $this;
		}
		if(!is_array($this->source)){
			return $this->addError('output',0);
		}
		if(!is_file($view)) {
			return $this->addError('output',1);
		}
		foreach($this->source as $key => $value){
			$$key = $value;
		}
		ob_start();
		include $view;
		return $this->setResult(ob_get_clean());
	}
}