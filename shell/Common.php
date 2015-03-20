<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

trait Singleton {
	private static $instance = null;
	private $vars = null;

	private function __construct() { if(method_exists($this,'__init')) $this->__init(); /* ... @return Singleton */ }  // Защищаем от создания через new Singleton
	private function __clone() { /* ... @return Singleton */ }  // Защищаем от создания через клонирование
	private function __wakeup() { /* ... @return Singleton */ }  // Защищаем от создания через unserialize

	public static function &_getInstance(){
		if(empty(self::$instance)){
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function &_($var,$val = null){
		if(!is_null($val)) {
			$this->vars[ $var ] = $val;
		}
		return $this->vars[$var];
	}
}

trait Shell {
	use Singleton;

	protected $_result = null;

	private function errors(){
		return Error::_getInstance()->get();
	}
	private function countErrors(){
		return Error::_getInstance()->count();
	}
	private function addError($key,$code,$details = array()){
		Error::_getInstance()->add($key,$code,$details);
		return $this;
	}

	private function result($result){
		$this->_result = $result;
		return $this;
	}
	public function __(&$to = null){
		if(is_null($to)){
			return $this->_result;
		}
		$to = $this->_result;
		return $this;
	}
}

trait Controller {
	use Shell;

	private function __init(){
		Lang::_getInstance()->load($this->_('type',get_class($this)));
		return $this;
	}

	public function _upsert($data = null, $id = null){
		return $this->result(DB::_getInstance()->upsert($this->_('type'), $this->_('lang'), $data, $id)->__());
	}
	public function _set($ids,$value = true,$field = 'Active'){
		DB::_getInstance()->set($this->_('type'), $ids, $value, $field);
		return $this;
	}
	public function _get($filter = null,$key = null,$with = null, $aggregate = null){
		return $this->result(DB::_getInstance()->get($this->_('type'), $this->_('lang'), $filter, $key, $with, $aggregate)->__());
	}
	public function _eq($n = 0){
		switch(true){
			case is_null($this->_result):
			case !is_array($this->_result):
			case is_assoc($this->_result):
			case $n >= count($this->_result):
			case $n < - count($this->_result):
				return $this->result(null);
			case $n < 0:
				$n += count($this->_result);
		}
		return $this->result($this->_result[$n]);
	}
	public function _drop(){
		if(empty($this->_result)){
			return $this;
		}
		if(is_assoc($this->_result)){
			$ids = array($this->_result['ID']);
		} else {
			$ids = array_map(function($i){return $i['ID'];},$this->_result);
		}
		DB::_getInstance()->drop($this->_('type'), $ids,$this->_('lang'));
		return $this;
	}
	public function _find($key,$value){
		foreach ($this->_result as $element) {
			if($element[$key] == $value){
				return $element;
			}
		}
		return null;
	}
	public function _dropImages(){
		if(empty($this->_result)){
			return $this;
		}
		if(is_assoc($this->_result)){
			$ids = array_values(array_intersect_key(
				$this->_result,
				array_fill_keys(preg_grep('/^ImageID\d*$/',array_keys($this->_result)),true)
			));
		} else {
			$ids = array();
			foreach($this->_result as $element){
				$ids = array_unique(array_merge($ids, array_values(array_intersect_key(
					$element,
					array_fill_keys(preg_grep('/^ImageID\d*$/',array_keys($element)),true)
				))));
			}
		}
		Image::_getInstance()->remove($ids);
		return $this;
	}
}

trait Content {
	use Controller;

	private $source = null;
	private $filter = null;
	private $path = null;

	public function create(){
		Config::_getInstance()->load($this->_('type'));
		switch(func_num_args()){
			case 0:
				include_once BASE_PATH . '/404.php';
			case 1:
				return $this->prepareSource()->parse(input('filter'))->prepareFilter()->save(func_get_arg(0))->finalize();
			default:
				$result = array();
				foreach(func_get_args() as $i => $id){
					$t = $this->prepareSource()->parse(input('filter') . "/$i")->prepareFilter()->save($id)->__();
					$result = array_merge($result,array_combine(array_map(postfix(($i) ? $i + 1 : ""),array_keys($t)),array_values($t)));
				}
				return $this->result($result)->finalize();
		}
	}
	public function remove($ids){
		switch(true){
			case is_array($ids):
				break;
			case !is_null(json_decode($ids)):
				$ids = json_decode($ids);
				break;
			default:
				$ids = array($ids);
		}
		if($this->_get($ids)->_drop()->countErrors()){
			return $this;
		}
		if(is_assoc($this->_result)){
			@unlink(BASE_PATH . $this->_result['URL']);
		} else {
			foreach($this->_result as $file){
				@unlink(BASE_PATH . $file['URL']);
			}
		}
		return $this;
	}

	public function _skel($filter = null,$path = null){
		if(is_null($filter)){
			$filter = config('filters',$this->_('type'));
			$path = BASE_PATH . config('contentPath','Default');
		}
		$result = true;
		foreach($filter as $k => $v){
			$path = "{$path}{$v}/";
			mkdir($path);
			if(!array_key_exists('.',$v)){
				$result &= $this->_skel($v,$path);
			}
		}
		return $result;
	}

	private function prepareSource(){
		if(!is_null($this->source)){
			return $this;
		}
		$this->source = array('tmp_name' => $_FILES[config('uploadFileIndex','Default')]['tmp_name']);
		if(!is_uploaded_file($this->source['tmp_name'])){
			return $this->addError('content',0);
		}
		return $this->processSource();
	}
	private function parse($filter){
		$filter = explode('/',$filter);
		switch(true){
			case empty($filter):
			case count($filter) == 1 && !array_key_exists($filter[0],config('filters',$this->_('type'))):
				$this->filter = config('filters',$this->_('type'))['Default'];
				$this->path = config('contentPath',$this->_('type')) . "default/";
				return $this;
			case count($filter) == 1:
				$this->filter = config('filters',$this->_('type'))[$filter[0]];
				$this->path = config('contentPath',$this->_('type')) . lcfirst($filter[0]) . "/";
				return $this;
			default:
				$this->filter = config('filters',$this->_('type'));
				$this->path = config('contentPath',$this->_('type'));
				foreach($filter as $i){
					if(array_key_exists($i,$this->filter)){
						$this->filter = $this->filter[$i];
						$this->path .= lcfirst($i) . "/";
					} else {
						$this->filter = config('filters',$this->_('type'))['Default'];
						$this->path = config('contentPath',$this->_('type')) . "default/";
					}
				}
				return $this;
		}
	}
}