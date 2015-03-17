<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class QueryBuilder {
	use Singleton;

	public function clean(){
		$this->_('lang',false);

		$this->_('select',array());
		$this->_('from',array());
		$this->_('join',array());
		$this->_('where',array());
		$this->_('groupBy',array());
		$this->_('insertInto',array());
		$this->_('values',array());
		$this->_('set',array());
		$this->_('update',array());
		return $this;
	}

	public function with($table,$with){
		foreach($with as $item){
			foreach($item['Fields'] as $field => $alias){
				$this->_('select')[] = "`{$item['Alias']}`.`$field` `$alias`";
			}
			$this->_('join')[] = "`{$item['Table']}` `{$item['Alias']}` ON(`{$table}`.`{$item['Key']}` = `{$item['Alias']}`.`ID`)";
			if(array_key_exists('LangFields',$item)){
				foreach($item['LangFields'] as $field => $alias){
					$this->_('select')[] = "`{$item['LangAlias']}`.`$field` `$alias`";
				}
				$this->_('join')[] = "`{$item['Table']}Lang` `{$item['LangAlias']}` ON(`{$item['LangAlias']}`.`{$item['Table']}ID` = `{$item['Alias']}`.`ID`)";
				$this->_('where')["{$item['LangAlias']}.Lang"] = $this->_('lang');
			}
		}
		return $this;
	}

	public function aggregate($table,$aggregate){
		foreach($aggregate as $item){
			$this->_('join')[] = "`{$item['Table']}` ON(`{$item['Table']}`.`{$table}ID` = `{$table}`.`ID`)";
			$this->_('where')[] = "{$item['Function']} `{$item['Alias']}`";
		}
		if(!empty($aggregate)) {
			$this->_('groupBy')[] = "`{$table}`.`ID`";
		}
		return $this;
	}

	public function build($queryType,$table,$data){
		return call_user_func(array($this,$queryType),$table,$data);
	}

	private function upsert($table,$data){
		$this->insertInto($table,array_keys($data));
		$this->values($data);
		if($this->_('lang')){
			unset($data["{$table}ID"]);
			unset($data['Lang']);
		} else {
			unset($data['ID']);
		}
		$this->update($table,$data);
		return "
		{$this->_('insertInto')}
		{$this->_('values')}
		ON DUPLICATE KEY UPDATE {$this->_('set')};";
	}
	private function set($table,$data){
		$this->update($table,$data['flags']);
		$this->where(array('ID' => $data['ids']));
		return "
		{$this->_('update')}
		SET {$this->_('set')}
		{$this->_('where')}
		;";
	}
	private function get($table,$data){
		$this->select($table,$this->_('lang') ? array("`{$table}`.*","`{$table}Lang`.*") : array("`{$table}`.*"));
		$this->from($table,$this->_('lang') ? array("`{$table}Lang` ON(`{$table}Lang`.`{$table}ID` = `{$table}`.`ID`)") : array());
		$this->where(empty($data) ? array() : $data);
		$this->groupBy();
		return "
		{$this->_('select')}
		{$this->_('from')}
		{$this->_('where')}
		{$this->_('groupBy')}
		;";
	}
	private function drop($table,$data){
		$this->from($table);
		$this->where($data);
		return "
		DELETE {$this->_('from')}
		{$this->_('where')}
		;";
	}

	private function insertInto($table,$keys = array()){
		$this->_('insertInto',array_merge($keys,$this->_('insertInto')));
		$this->_('insertInto',"`$table`(`" . implode('`,`',$this->_('insertInto')) . "`)");
		return $this;
	}
	private function values($values = array()){
		$this->_('values',array_merge($values,$this->_('values')));
		$this->_('values',"VALUES(" . implode(',',
			array_map(function($k){
				switch($k){
					case null:
						return 'NULL';
					case false:
						return 'FALSE';
					case true:
						return 'TRUE';
					default:
						return "'$k'";
				}
			},array_values($this->_('values')))) . ")");
		return $this;
	}
	private function update($table,$data = array()){
		$this->_('update',"UPDATE `{$table}`");
		$this->_('set',array_merge($data,$this->_('set')));
		$this->_('set',implode(',',
			array_map(function($k,$v){
				$k = '`' . implode('`.`',explode('.',$k)) . '`';
				switch($v){
					case null:
						return "$k = NULL";
					case true:
						return "$k = TRUE";
					case false:
						return "$k = FALSE";
					default:
						return "$k = '$v'";
				}
			},array_keys($this->_('set')),array_values($this->_('set')))));
		return $this;
	}
	private function select($table,$fields = array()){
		$this->_('select',array_merge($fields,$this->_('select'),array("`{$table}`.`ID` `ID`")));
		$this->_('select',"SELECT " . implode(', ',$this->_('select')));
		return $this;
	}
	private function from($table,$join = array()){
		$this->_('join',array_merge($join,$this->_('join')));
		$this->_('from',"FROM `{$table}`" . (empty($this->_('join')) ? "" : " LEFT OUTER JOIN " .  implode(' LEFT OUTER JOIN ',$this->_('join'))));
		return $this;
	}
	private function where($data = array()){
		$this->_('where',array_merge($data,$this->_('where')));
		$this->_('where',empty($this->_('where')) ? "" : "WHERE " . implode(' AND ',array_map(function($k,$v){
				$k = '`' . implode('`.`',explode('.',$k)) . '`';
				switch(true){
					case is_null($v):
						return "$k IS NULL";
					case $v === true:
						return "$k = true";
					case $v === false:
						return "$k = false";
					case is_array($v):
						return "$k IN('" . implode('\',\'',$v) . "')";
					default:
						return "$k = '$v'";
				}
			},array_keys($this->_('where')),array_values($this->_('where')))));
		return $this;
	}
	private function groupBy(){
		$this->_('groupBy',empty($this->_('groupBy')) ? "" : "GROUP BY " . implode(', ',$this->_('groupBy')));
		return $this;
	}
}