<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

abstract class Singleton {
	/**
	 * @var array
	 */
	private static $instances;
	/**
	 * @var array
	 */
	private $vars = null;

	abstract protected function __init();

	private function __construct() { $this->__init(); /* ... @return Singleton */ }  // Защищаем от создания через new Singleton
	private function __clone() { /* ... @return Singleton */ }  // Защищаем от создания через клонирование
	private function __wakeup() { /* ... @return Singleton */ }  // Защищаем от создания через unserialize

	/**
	 * @return $this
	 */
	public static function &_getInstance(){
		$class = get_called_class();
		if(!isset(self::$instances[$class])){
			self::$instances[$class] = new $class();
		}
		return self::$instances[$class];
	}

	/**
	 * @param string $var
	 * @param mixed $val
	 * @return mixed
	 */
	public function &_($var,$val = null){
		if(!is_null($val)) {
			$this->vars[ $var ] = $val;
		}
		return $this->vars[$var];
	}
}

abstract class Shell extends Singleton {
	/**
	 * @var array
	 */
	protected $_result = null;

	/**
	 * @return array
	 */
	protected function errors(){
		return Error::_getInstance()->get();
	}

	/**
	 * @return int
	 */
	protected function countErrors(){
		return Error::_getInstance()->count();
	}

	/**
	 * @param string $key
	 * @param string $code
	 * @param array $details
	 * @return $this
	 */
	protected function addError($key,$code,$details = array()){
		Error::_getInstance()->add($key,$code,$details);
		return $this;
	}

	/**
	 * @return $this
	 */
	protected function flushErrors(){
		Error::_getInstance()->flush();
		return $this;
	}

	/**
	 * @param array $result
	 * @return $this
	 */
	protected function result($result){
		$this->_result = $result;
		return $this;
	}

	/**
	 * @param mixed $to
	 * @return $this|array
	 */
	public function __(&$to = null){
		if(is_null($to)){
			return $this->_result;
		}
		$to = $this->_result;
		return $this;
	}
}

abstract class Controller extends Shell {
	protected function __init(){
		Lang::_getInstance()->load($this->_('type',get_class($this)));
		return $this;
	}

	/**
	 * @return null|string
	 */
	private function _lang(){
		if(array_key_exists($this->_('type') . "Lang", config('rules','Validation'))){
			return input('lang');
		}
		return null;
	}

	/**
	 * @param array $data
	 * @param int $id
	 * @return $this
	 */
	public function _upsert($data = null, $id = null){
		return $this->result(DB::_getInstance()->upsert($this->_('type'), $this->_lang(), $data, $id)->__());
	}

	/**
	 * @param int|array $ids
	 * @param bool $value
	 * @param string|array $field
	 * @return $this
	 */
	public function _set($ids,$value = true,$field = 'Active'){
		DB::_getInstance()->set($this->_('type'), $ids, $field, $value);
		return $this;
	}

	/**
	 * @param string|array $filter
	 * @param string $key
	 * @param string|array $with
	 * @param string|array $aggregate
	 * @return $this
	 */
	public function _get($filter = null,$key = null,$with = null, $aggregate = null){
		return $this->result(DB::_getInstance()->get($this->_('type'), $this->_lang(), $filter, $key, $with, $aggregate)->__());
	}

	/**
	 * @param int $n
	 * @return $this
	 */
	public function _eq($n = 0){
		switch(true){
			case is_null($this->_result):
			case !is_array($this->_result):
			case $n >= count($this->_result):
			case $n < - count($this->_result):
			case (is_assoc($this->_result) && $n != 0 && $n != -1):
				return $this->result(null);
			case is_assoc($this->_result):
				return $this;
			case $n < 0:
				$n += count($this->_result);
		}
		return $this->result($this->_result[$n]);
	}

	/**
	 * @param string|array $with
	 * @return $this
	 */
	public function _drop($with = array()){
		if (empty($this->_result)) {
			return $this;
		}
		$ids = $this->_field('ID');
		foreach(Validation::_getInstance()->getDependencies($this->_('type')) as $objectType => $keys){
			foreach($keys as $key){
				_getInstance($objectType)->_get($ids,$key)->_drop($with);
			}
		}
		DB::_getInstance()->drop($this->_('type'), $ids, $this->_lang());
		$dropDependenciesQueue = array();
		foreach(is_array($with) ? $with : array($with) as $k => $v){
			if(is_numeric($k)){
				$objectType = $v;
				$dependencies = array();
			} else {
				$objectType = $k;
				$dependencies = $v;
			}
			if($ids = $this->_getReferences($objectType)){
				$dropDependenciesQueue[] = array('objectType' => $objectType,'dependencies' => $dependencies,'ids' => $ids);
			}
		}
		foreach($dropDependenciesQueue as $dependency){
			_getInstance($dependency['objectType'])->_get($dependency['ids'])->_drop($dependency['dependencies']);
		}
		return $this;
	}

	/**
	 * @param string $key
	 * @param string $value
	 * @return array|null
	 */
	public function _find($key,$value){
		foreach ($this->_result as $element) {
			if($element[$key] == $value){
				return $element;
			}
		}
		return null;
	}

	/**
	 * @return array|null
	 */
	public function _first(){
		return $this->_eq(0)->__();
	}

	/**
	 * @return array|null
	 */
	public function _last(){
		return $this->_eq(-1)->__();
	}

	/**
	 * @param string $key
	 * @return array
	 */
	public function _field($key){
		if (is_assoc($this->_result)) {
			return array($this->_result[$key]);
		} else {
			return array_map(function ($i) use($key) {
				return $i[$key];
			}, $this->_result);
		}
	}

	/**
	 * @param string $referenceType
	 * @return array
	 */
	public function _getReferences($referenceType){
		if(empty($this->_result)){
			return array();
		}
		if(is_assoc($this->_result)){
			$ids = array_values(array_intersect_key(
				$this->_result,
				array_fill_keys(preg_grep("/^{$referenceType}ID\d*\$/",array_keys($this->_result)),true)
			));
		} else {
			$ids = array();
			foreach($this->_result as $element){
				$ids = array_unique(array_merge($ids, array_values(array_intersect_key(
					$element,
					array_fill_keys(preg_grep("/^{$referenceType}ID\d*\$/",array_keys($element)),true)
				))));
			}
		}
		return $ids;
	}
}

abstract class Content extends Controller {
	/**
	 * @var array
	 */
	protected $source = null;
	/**
	 * @var array
	 */
	protected $filter = null;
	/**
	 * @var string
	 */
	protected $path = null;

	abstract protected function processSource();

	abstract protected function prepareFilter();

	abstract protected function save();

	abstract protected function finalize();

	/**
	 * @return $this
	 */
	public function create(){
		Config::_getInstance()->load($this->_('type'));
		switch(func_num_args()){
			case 0:
				return error404();
			case 1:
				return $this->prepareSource()->parse(input('filter'))->prepareFilter()->save(func_get_arg(0))->finalize();
			default:
				$result = array();
				foreach(func_get_args() as $i => $id){
					if($id == -1){
						$result = array_merge($result,array("ID" . ($i + 1) => -1));
						continue;
					}
					$t = $this->prepareSource()->parse(input('filter') . "/$i")->prepareFilter()->save($id)->__();
					$result = array_merge($result,array_combine(array_map(postfix(($i) ? $i + 1 : ""),array_keys($t)),array_values($t)));
				}
				return $this->result($result)->finalize();
		}
	}

	/**
	 * @param mixed $ids
	 * @return $this
	 */
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
		return $this->_get($ids)->_drop();
	}

	public function _drop($with = array()){
		if(parent::_drop($with)->countErrors()){
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

	/**
	 * @param array $filter
	 * @param string $path
	 * @return bool
	 */
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

	/**
	 * @return $this
	 */
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

	/**
	 * @param array $filter
	 * @return $this
	 */
	private function parse($filter){
		$filter = explode('/',$filter);
		switch(true){
			case empty($filter):
			case count($filter) == 1 && !array_key_exists($filter[0],config('filters',$this->_('type'))):
				$this->filter = config('filters',$this->_('type'))['Default'];
				$this->path = config('contentPath','Default') . lcfirst($this->_('type')) . "/default/";
				return $this;
			case count($filter) == 1:
				$this->filter = config('filters',$this->_('type'))[$filter[0]];
				$this->path = config('contentPath','Default') . lcfirst($this->_('type')) . "/" . lcfirst($filter[0]) . "/";
				return $this;
			default:
				$this->filter = config('filters',$this->_('type'));
				$this->path = config('contentPath','Default') . lcfirst($this->_('type')) . "/";
				foreach($filter as $i){
					if(array_key_exists($i,$this->filter)){
						$this->filter = $this->filter[$i];
						$this->path .= lcfirst($i) . "/";
					} else {
						$this->filter = config('filters',$this->_('type'))['Default'];
						$this->path = config('contentPath','Default') . lcfirst($this->_('type')) . "/default/";
					}
				}
				return $this;
		}
	}
}

/**
 * @param string $class
 * @return Singleton|Shell|Controller|Content
 */
function _getInstance($class){
	return call_user_func(array($class,'_getInstance'));
}