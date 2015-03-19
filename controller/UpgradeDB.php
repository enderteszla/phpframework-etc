<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class UpgradeDB {
	use Controller;

	public function index(){
		return $this->run();
	}
	public function test(){
		Error::_getInstance()->flush()->_('verbose','die');
		if(is_null($this->run()->run('test')->__())){
			lang('upToDate','UpgradeDB');
			echo '<br /';
			return $this;
		}
		if(removeContent(BASE_PATH . '/content/')) {
			lang('removeContentSuccess','UpgradeDB');
			echo '<br />';
		}
		if(copyContent(BASE_PATH . '/test/content/',BASE_PATH . '/content/')) {
			lang('copyContentSuccess','UpgradeDB');
			echo '<br />';
		}
		return $this;
	}

	private function run($type = 'core'){
		$db = DB::_getInstance();
		switch($type){
			case 'test':
				$sqlPath = BASE_PATH . '/test/sql/';
				break;
			default:
				$type = 'core';
				$sqlPath = BASE_PATH . '/sql/';
				$db->query(file_get_contents($sqlPath . "UpgradeDB.sql"));
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