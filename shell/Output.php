<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Output {
	use Shell;

	private $source = null;

	private function __init(){
		Config::getInstance()->load('Output');
	}

	public function setSource($source){
		$this->source = $source;
		return $this;
	}
	public function expose(){
		$dataType = input('json') ? 'json' :
			($_SERVER['REQUEST_METHOD'] == 'POST' ? 'viewInJson' : 'view');
		call_user_func_array(array($this,$dataType),func_get_args());
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