<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class UpgradeDB extends Controller {
	/**
	 * @return $this
	 */
	public function index(){
		Config::_getInstance()->load('UpgradeDB');
		if(!config('enabled','UpgradeDB')){
			lang('disabled','UpgradeDB');
			exit();
		}
		Error::_getInstance()->flush()->_('verbosity','die');

		if(!removeContent(BASE_PATH . config('contentPath','Default'))){
			lang('removeContentFailure','UpgradeDB');
			exit();
		}
		lang('removeContentSuccess','UpgradeDB');
		linefeed();

		foreach(config('content','UpgradeDB') as $type){
			Config::_getInstance()->load($type);
			if(!$this->skeleton(config('filters',$type))){
				lang('skeletonContentFailure','UpgradeDB',array($type));
				exit();
			}
			lang('skeletonContentSuccess','UpgradeDB',array($type));
			linefeed();
		}
		return $this->run();
	}

	/**
	 * @return $this
	 */
	public function test(){
		Config::_getInstance()->load('UpgradeDB');
		if(!config('enabled','UpgradeDB')){
			lang('disabled','UpgradeDB');
			exit();
		}
		Error::_getInstance()->flush()->_('verbosity','die');

		if(is_null($this->index()->run('test')->__())){
			lang('upToDate','UpgradeDB');
			exit();
		}

		if(!copyContent(BASE_PATH . config('testContentPath','UpgradeDB'),BASE_PATH . config('contentPath','Default'))) {
			lang('copyContentFailure','UpgradeDB');
		}
		lang('copyContentSuccess','UpgradeDB');
		linefeed();
		return $this;
	}

	/**
	 * @param array $filter
	 * @param string $path
	 * @return bool
	 */
	private function skeleton($filter,$path = null){
		if(is_null($path)){
			$path = BASE_PATH . config('contentPath','Default');
		}
		$result = true;
		foreach($filter as $k => $v){
			if(is_dir($newPath = $path . lcfirst($k) ."/")){
				continue;
			}
			mkdir($newPath);
			if(!array_key_exists('.',$v)){
				$result &= $this->skeleton($v,$newPath);
			}
		}
		return $result;
	}

	/**
	 * @param string $type
	 * @return $this
	 */
	private function run($type = 'core'){
		$db = DB::_getInstance();
		switch($type){
			case 'test':
				$sqlPath = BASE_PATH . config('testSqlPath','UpgradeDB');
				break;
			default:
				$type = 'core';
				$sqlPath = BASE_PATH . config('sqlPath','UpgradeDB');
				$db->query(file_get_contents($sqlPath . "UpgradeDB.sql"));
		}
		if(!is_dir($sqlPath)){
			return $this->result(null);
		}
		if(is_null($this->_get($type,'Type')->_eq()->__())){
			$start = -1;
			$id = null;
		} else {
			if($type == 'test'){
				return $this->result(null);
			}
			$start = (int)$this->__()['Version'];
			$id = $this->__()['ID'];
		}
		$last = 0;
		foreach(preg_grep("/^\d{4}\.sql$/",scandir($sqlPath)) as $file) {
			$last = (int)str_replace(".sql","",$file);
			if($start < $last){
				$db->query(file_get_contents($sqlPath . $file));
			}
		}
		$version = ($type == 'test') ? "0000" : sprintf("%'04d",$last);
		return $this->_upsert(array(
			'Version' => $version,
			'Type' => $type
		),$id);
	}
}