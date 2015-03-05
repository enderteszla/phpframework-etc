<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

require_once SHELL_PATH . 'Validation.php';

class DB {
	use Shell;

	private $link = null;

	private function __init(){
		$DB = array();
		include_once CONFIG_PATH . 'DB.php';
		$this->link = new mysqli($DB['host'],$DB['user'],$DB['password'],$DB['db']);
		if($this->link->connect_error){
			$this->addError('connection',$this->link->connect_errno,$this->link->connect_error);
		}
		$this->link->set_charset("utf8");
	}

	public function upsert($table,$lang = false,$data = null,$id = null){
		if(!is_null($data)) {
			if(empty($lang)){
				$lang = false;
				$this->addErrors(Validation::getInstance()->setTable($table)->process($data,null,'required')->errors());
			} else {
				$this->addErrors(Validation::getInstance()->setTable($table)->process($dataLang = clone $data,false,'required')->process($data,null,'required')->processLocale($lang)->errors());
			}
		}
		if($this->addErrors(
			is_null($id) ? array() : Validation::getInstance()->processID($id)->putResult($id)->errors()
		)->_errorsNumber){
			return $this;
		}
		$data['ID'] = $id;
		$into = "`$table`(`" . implode('`,`',array_keys($data)) . "`)";
		$values = $this->prepareQuery('values',$data);
		unset($data['ID']);
		$update = $this->prepareQuery('update',$data);
		if(!($res = $this->link->query("INSERT INTO $into VALUES $values ON DUPLICATE KEY UPDATE $update;"))){
			return $this->addError('insert/update',$this->link->errno,$this->link->error);
		}
		$id = ($id == 0) ? $this->link->insert_id : $id;
		if($lang){
			$dataLang["{$table}ID"] = $id;
			$dataLang['Lang'] = $lang;
			$into = "`{$table}Lang`(`" . implode('`,`',array_keys($dataLang)) . "`)";
			$values = $this->prepareQuery('values',$dataLang);
			unset($dataLang["{$table}ID"]);
			unset($dataLang['Lang']);
			$update = $this->prepareQuery('update',$dataLang);
			if(!($res = $this->link->query("INSERT INTO $into VALUES $values ON DUPLICATE KEY UPDATE $update;"))){
				return $this->addError('insert/update',$this->link->errno,$this->link->error);
			}
		}
		return $this->get($table,$lang,$id);
	}
	public function set($table,$ids,$value = true,$field = 'Active'){
		if(!is_array($ids)){
			$ids = array($ids);
		}
		if($value === true){
			$value = 'true';
		} else {
			$value = 'false';
		}
		if(!$this->addErrors(Validation::getInstance()->processID($ids)->putResult($ids)->errors())->_errorsNumber){
			if(!($res = $this->link->query("UPDATE `{$table}` SET `{$field}` = $value WHERE `ID` IN(" . implode(',',$ids) . ");"))){
				return $this->addError('set',$this->link->errno,$this->link->error);
			}
		}
		return $this;
	}
	public function get($table,$lang = false,$filter = null,$key = null,$checkActive = false){
		$this->result = null;
		if(!is_null($filter)) {
			if (!is_array($filter) || !is_assoc($filter)) {
				if(is_null($key)){
					$key = 'ID';
				}
				$filter = array($key => $filter);
			}
			if(array_key_exists('ID',$filter)){
				$this->addErrors(Validation::getInstance()->setTable($table)->processID($filter['ID'])->process($filter,empty($lang) ? null : true)->putResult($filter['ID'])->errors());
			} else {
				$this->addErrors(Validation::getInstance()->setTable($table)->process($filter,empty($lang) ? null : true)->errors());
			}
		}
		if(empty($lang)) {
			$lang = false;
		} else {
			$filter['Lang'] = $lang;
			$this->addErrors(Validation::getInstance()->processLocale($lang)->errors());
		}
		if($checkActive === true){
			$filter['Active'] = true;
		}
		if($this->_errorsNumber){
			return $this;
		}
		$select = (($lang !== false) ? "`{$table}Lang`.*, " : "") . "`{$table}`.*";
		$from = "`$table`" . (($lang !== false) ? " JOIN `{$table}Lang` ON(`{$table}Lang`.`{$table}ID` = `{$table}`.`ID`)" : "");
		$where = is_null($filter) ? "" : "WHERE " . implode(' AND ',array_map(function($k,$v){return is_null($v) ? "`$k` IS NULL" : (is_array($v) ? "`$k` IN('" . implode('\',\'',$v) . "')" : "`$k` = '$v'");},array_keys($filter),array_values($filter)));
		if(!($res = $this->link->query("SELECT $select FROM $from $where;"))){
			return $this->addError('select',$this->link->errno,$this->link->error);
		}
		if(is_array($filter) && array_key_exists('ID',$filter) && !is_array($filter['ID'])){
			if($res->num_rows == 0){
				return $this->addError('select',0,array($table,$filter['ID']));
			}
			$this->result = $res->fetch_assoc();
		} else {
			if($res->num_rows > 0) {
				$this->result = array();
				while($row = $res->fetch_assoc()){
					$this->result[] = $row;
				}
			} else {
				$this->result = null;
			}
		}
		return $this;
	}
	public function drop($table,$ids){
		if(!is_array($ids)){
			$ids = array($ids);
		}
		if(!$this->addErrors(Validation::getInstance()->processID($ids)->putResult($ids)->errors())->_errorsNumber){
			if(!($res = $this->link->query("DELETE FROM `{$table}` WHERE `ID` IN(" . implode(',',$ids) . ");"))){
				return $this->addError('drop',$this->link->errno,$this->link->error);
			}
		}
		return $this;
	}
	public function makeIndexedArray(){
		if(!$this->_errorsNumber && is_array($this->result) && !empty($this->result)){
			$return = array();
			foreach($this->result as $element){
				$return[$element['ID']] = $element;
			}
			$this->result = $return;
		}
		return $this;
	}

	private function prepareQuery($queryType,$data){
		switch($queryType){
			case 'where':
				return is_null($data) ? "" :
					"WHERE " . implode(' AND ',
						array_map(function($k,$v){
							return is_null($v) ? "`$k` IS NULL" :
								(is_array($v) ? "`$k` IN('" . implode('\',\'',$v) . "')" :
									(is_bool($v) ? "`$k` = $v" : "`$k` = '$v'"));
						},array_keys($data),array_values($data)));
			case 'values':
				return "(" . implode(',',
					array_map(function($k){
						return is_null($k) ? 'NULL' :
							"'$k'";
					},array_values($data))) . ")";
			case 'update':
				return implode(',',
					array_map(function($k,$v){
						return is_null($v) ? "`$k`=NULL" :
							"`$k`='$v'";
					},array_keys($data),array_values($data)));
			default:
				return "";
		}
	}
}
