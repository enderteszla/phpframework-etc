<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class UpgradeDB {
	use Controller;

	public function index(){
		return $this->run();
	}
	public function test(){
		removeContent(BASE_PATH . '/content/');
		copyContent(BASE_PATH . '/test/content/',BASE_PATH . '/content/');
		return $this->run()->run('test');
	}

	private function run($type = 'core'){
		switch($type){
			case 'test':
				$sqlPath = BASE_PATH . '/test/sql/';
				break;
			default:
				$sqlPath = BASE_PATH . '/sql/';
				$type = 'core';
		}
		Error::_getInstance()->flush();
		$db = DB::_getInstance();
		if($type == "core") {
			$db->query(file_get_contents($sqlPath . "UpgradeDB.sql"));
		}
		if(is_null($this->_get($type,'Type')->_eq()->__())){
			$start = -1;
			$id = null;
		} else {
			$start = (int)$this->__()['Version'];
			$id = $this->__()['ID'];
		}
		$last = 0;
		foreach(array_filter(scandir($sqlPath),function($f){return !in_array($f,array('.','..','UpgradeDB.sql'));}) as $file){
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