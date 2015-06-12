<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class QueryBuilder extends Singleton {
	protected function __init(){
		$this->clean();
	}

	/**
	 * @return $this
	 */
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

	/**
	 * @param array $with
	 * @return $this
	 */
	public function with($with){
		foreach ($with as $alias => $array) {
			foreach($array['Fields'] as $key => $fieldAlias){
				$this->_('select')[] = "`$alias`.`$key` `$fieldAlias`";
			}
			$this->_('join')[] = "`{$array['Table']}` `$alias` ON(`{$array['JoinedTable']}`.`{$array['Key']}` = `$alias`.`ID`)";
			if(array_key_exists('LangFields',$array)){
				foreach($array['LangFields'] as $key => $fieldAlias){
					$this->_('select')[] = "`{$array['LangAlias']}`.`$key` `$fieldAlias`";
				}
				$this->_('join')[] = "`{$array['Table']}Lang` `{$array['LangAlias']}` ON(`{$array['LangAlias']}`.`{$array['Table']}ID` = `$alias`.`ID`)";
				$this->_('where')["{$array['LangAlias']}.Lang"] = $this->_('lang');
			}
		}
		return $this;
	}

	/**
	 * @param array $aggregate
	 * @return $this
	 */
	public function aggregate($aggregate){
		foreach($aggregate as $alias => $array){
			$this->_('join')[] = "`{$array['JoiningTable']}` `{$alias}` ON(`{$alias}`.`{$array['JoinedTable']}ID` = `{$array['JoinedTableAlias']}`.`ID`)";
			foreach($array['Fields'] as $fieldAlias => $function){
				$this->_('select')[] = "$function `$fieldAlias`";
			}
		}
		return $this;
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @return string
	 */
	public function upsert($table,$data){
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

	/**
	 * @param string $table
	 * @param array $data
	 * @return string
	 */
	public function set($table,$data){
		$this->update($table,$data['flags']);
		$this->where(array('ID' => $data['ids']));
		return "
		{$this->_('update')}
		SET {$this->_('set')}
		{$this->_('where')}
		;";
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @return string
	 */
	public function get($table,$data){
		$this->select($table,$this->_('lang') ? array("`{$table}`.*","`{$table}Lang`.*") : array("`{$table}`.*"));
		$this->from($table,$this->_('lang') ? array("`{$table}Lang` ON(`{$table}Lang`.`{$table}ID` = `{$table}`.`ID`)") : array());
		$this->where(empty($data) ? array() : $data);
		$this->groupBy($table);
		return "
		{$this->_('select')}
		{$this->_('from')}
		{$this->_('where')}
		{$this->_('groupBy')}
		;";
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @return string
	 */
	public function drop($table,$data){
		$this->from($table);
		$this->where($data);
		return "
		DELETE {$this->_('from')}
		{$this->_('where')}
		;";
	}

	/**
	 * @param string $table
	 * @param array $keys
	 * @return $this
	 */
	private function insertInto($table,$keys = array()){
		$this->_('insertInto',array_merge($keys,$this->_('insertInto')));
		$this->_('insertInto',"INSERT INTO `$table`(`" . implode('`,`',$this->_('insertInto')) . "`)");
		return $this;
	}

	/**
	 * @param array $values
	 * @return $this
	 */
	private function values($values = array()){
		$this->_('values',array_merge($values,$this->_('values')));
		$this->_('values',"VALUES(" . implode(',',
			array_map(function($k){
				switch(true){
					case is_null($k):
						return 'NULL';
					case $k === false:
						return 'FALSE';
					case $k === true:
						return 'TRUE';
					default:
						return "'$k'";
				}
			},array_values($this->_('values')))) . ")");
		return $this;
	}

	/**
	 * @param string $table
	 * @param array $data
	 * @return $this
	 */
	private function update($table,$data = array()){
		$this->_('update',"UPDATE `{$table}`");
		$this->_('set',array_merge($data,$this->_('set')));
		$this->_('set',implode(',',
			array_map(function($k,$v){
				$k = '`' . implode('`.`',explode('.',$k)) . '`';
				switch(true){
					case is_null($v):
						return "$k = NULL";
					case $v === true:
						return "$k = TRUE";
					case $v === false:
						return "$k = FALSE";
					default:
						return "$k = '$v'";
				}
			},array_keys($this->_('set')),array_values($this->_('set')))));
		return $this;
	}

	/**
	 * @param string $table
	 * @param array $fields
	 * @return $this
	 */
	private function select($table,$fields = array()){
		$this->_('select',array_merge($fields,array("`{$table}`.`ID` `__ID__`"),$this->_('select')));
		$this->_('select',"SELECT " . implode(', ',$this->_('select')));
		return $this;
	}

	/**
	 * @param string $table
	 * @param array $join
	 * @return $this
	 */
	private function from($table,$join = array()){
		$this->_('join',array_merge($join,$this->_('join')));
		$this->_('from',"FROM `{$table}`" . (!$this->_('join') ? "" : " LEFT OUTER JOIN " .  implode(' LEFT OUTER JOIN ',$this->_('join'))));
		return $this;
	}

	/**
	 * @param array $data
	 * @return $this
	 */
	private function where($data = array()){
		$this->_('where',array_merge($data,$this->_('where')));
		$this->_('where',!$this->_('where') ? "" : "WHERE " . implode(' AND ',array_map(function($k,$v){
				$k = '`' . implode('`.`',explode('.',$k)) . '`';
				switch(true){
					case is_null($v):
						return "$k IS NULL";
					case $v === true:
						return "$k = TRUE";
					case $v === false:
						return "$k = FALSE";
					case is_array($v):
						$return = "$k IN(" . implode(',',array_map(function($value){
							switch(true){
								case is_null($value):
									return 'NULL';
								case $value === false:
									return 'FALSE';
								case $value === true:
									return 'TRUE';
								default:
									return "'$value'";
							}
						},$v)) . ")";
						if(in_array(null,$v,true)){
							return "($k IS NULL OR $return)";
						}
						return $return;
					default:
						return "$k = '$v'";
				}
			},array_keys($this->_('where')),array_values($this->_('where')))));
		return $this;
	}

	/**
	 * @param string $table
	 * @return $this
	 */
	private function groupBy($table){
		$this->_('groupBy',"GROUP BY `{$table}`.`ID`");
		return $this;
	}
}