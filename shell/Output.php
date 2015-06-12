<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Output extends Shell {
	/**
	 * @var array
	 */
	private $source = null;

	protected function __init(){
		Config::_getInstance()->load('Output');
	}

	/**
	 * @param array $source
	 * @return $this
	 */
	public function setSource($source){
		if(!is_array($source)){
			return $this->addError('output',0);
		}
		$this->source = $source;
		return $this;
	}

	/**
	 * @return $this
	 */
	public function expose(){
		if(IS_CLI){
			return $this;
		}
		$dataType = input('json') ? 'json' :
			($_SERVER['REQUEST_METHOD'] == 'POST' ? 'viewInJson' : 'view');
		call_user_func_array(array($this,$dataType),func_get_args());
		return $this;
	}

	/**
	 * @param string $view
	 * @param bool $return
	 * @return $this
	 */
	private function view($view,$return = false){
		if(!is_file($view)) {
			$this->addError('output',1);
		}
		if($this->countErrors()){
			if($return){
				return $this;
			}
			return error404();
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

	/**
	 * @return $this
	 */
	private function json(){
		if($this->countErrors() > 0){
			$data = array(
				'status' => 'Fail',
				'data' => array('errors' => $this->errors())
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

	/**
	 * @param string $view
	 * @return $this
	 */
	private function viewInJson($view){
		return $this->setSource($this->view($view,true)->__())->json();
	}
}