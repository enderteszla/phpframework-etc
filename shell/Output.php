<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Output {
	use Shell;

	private $source = null;

	private function __init(){
		Config::_getInstance()->load('Output');
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

	private function view($view,$return = false){
		if(!is_array($this->source)){
			return $this->addError('output',0);
		}
		if(!is_file($view)) {
			return $this->addError('output',1);
		}
		if($this->countErrors()){
			if($return){
				return $this;
			}
			include BASE_PATH . '/404.php';
		}
		foreach($this->source as $key => $value){
			$$key = $value;
		}
		ob_start();
		include $view;
		$content = ob_get_clean();
		if($return){
			return $this->result($content);
		}
		include VIEW_PATH . 'root.php';
		return $this;
	}
	private function json(){
		if($this->countErrors() > 0){
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
		return $this->setSource($this->view($view,true)->__())->json();
	}
}