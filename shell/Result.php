<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

/* MySQL result type casting:
0   MYSQLI_TYPE_DECIMAL	    DECIMAL             int
1	MYSQLI_TYPE_TINY	    TINYINT             int
	MYSQLI_TYPE_CHAR
2	MYSQLI_TYPE_SHORT	    SMALLINT            int
3	MYSQLI_TYPE_LONG	    INT                 int
4	MYSQLI_TYPE_FLOAT	    FLOAT               float
5	MYSQLI_TYPE_DOUBLE	    DOUBLE              double
6	MYSQLI_TYPE_NULL	    DEFAULT NULL        ???
7	MYSQLI_TYPE_TIMESTAMP	TIMESTAMP           string
8	MYSQLI_TYPE_LONGLONG	BIGINT              int
9	MYSQLI_TYPE_INT24	    MEDIUMINT           int
10	MYSQLI_TYPE_DATE	    DATE                string
11	MYSQLI_TYPE_TIME	    TIME                string
12	MYSQLI_TYPE_DATETIME	DATETIME            string
13	MYSQLI_TYPE_YEAR	    YEAR                string
14	MYSQLI_TYPE_NEWDATE	    DATE                string
16	MYSQLI_TYPE_BIT		    BIT                 string
246	MYSQLI_TYPE_NEWDECIMAL	DECIMAL | NUMERIC   int
247	MYSQLI_TYPE_INTERVAL	INTERVAL            string
	MYSQLI_TYPE_ENUM        ENUM                string
248	MYSQLI_TYPE_SET		    SET                 string
249	MYSQLI_TYPE_TINY_BLOB	TINYBLOB            ???
250	MYSQLI_TYPE_MEDIUM_BLOB	MEDIUMBLOB          ???
251	MYSQLI_TYPE_LONG_BLOB	LONGBLOB            ???
252	MYSQLI_TYPE_BLOB	    BLOB                ???
253	MYSQLI_TYPE_VAR_STRING	VARCHAR             string
254	MYSQLI_TYPE_STRING	    CHAR | BINARY       string
255	MYSQLI_TYPE_GEOMETRY	GEOMETRY            ???
 */

class Result {
	use Singleton;

	private $types = null;
	private $rules = null;

	private function __init(){
		$this->types = array();
		foreach(get_defined_constants(true)['mysqli'] as $c => $n){
			if (preg_match('/^MYSQLI_TYPE_(.*)$/', $c, $m)) $this->types[$n] = $m[0];
		}
		$this->rules = array(
			'MYSQLI_TYPE_DECIMAL'       => 'int',
			'MYSQLI_TYPE_TINY'          => 'int',
			'MYSQLI_TYPE_CHAR'          => 'int',
			'MYSQLI_TYPE_SHORT'         => 'int',
			'MYSQLI_TYPE_LONG'          => 'int',
			'MYSQLI_TYPE_FLOAT'         => 'float',
			'MYSQLI_TYPE_DOUBLE'        => 'double',
			'MYSQLI_TYPE_NULL'          => 'undefined',
			'MYSQLI_TYPE_TIMESTAMP'     => 'string',
			'MYSQLI_TYPE_LONGLONG'      => 'int',
			'MYSQLI_TYPE_INT24'         => 'int',
			'MYSQLI_TYPE_DATE'          => 'string',
			'MYSQLI_TYPE_TIME'          => 'string',
			'MYSQLI_TYPE_DATETIME'      => 'string',
			'MYSQLI_TYPE_YEAR'          => 'string',
			'MYSQLI_TYPE_NEWDATE'       => 'string',
			'MYSQLI_TYPE_BIT'           => 'string',
			'MYSQLI_TYPE_NEWDECIMAL'    => 'int',
			'MYSQLI_TYPE_INTERVAL'      => 'string',
			'MYSQLI_TYPE_ENUM'          => 'string',
			'MYSQLI_TYPE_SET'           => 'string',
			'MYSQLI_TYPE_TINY_BLOB'     => 'string',
			'MYSQLI_TYPE_MEDIUM_BLOB'   => 'string',
			'MYSQLI_TYPE_LONG_BLOB'     => 'string',
			'MYSQLI_TYPE_BLOB'          => 'string',
			'MYSQLI_TYPE_VAR_STRING'    => 'string',
			'MYSQLI_TYPE_STRING'        => 'string',
			'MYSQLI_TYPE_GEOMETRY'      => 'undefined'
		);
	}

	public function fetch($mysqli_result,$single){
		$this->_('fields',array());
		foreach($mysqli_result->fetch_fields() as $field){
			$this->_('fields')[$field->name] = $this->rules[$this->types[$field->type]];
		}
		switch(true){
			case !$mysqli_result:
				return null;
			case $single && $mysqli_result->num_rows == 0:
				$result = false;
				break;
			case $single:
				$result = $this->cast($mysqli_result->fetch_assoc());
				break;
			case $mysqli_result->num_rows == 0:
				$result = array();
				break;
			default:
				$result = array();
				while($row = $mysqli_result->fetch_assoc()){
					$result[] = $this->cast($row);
				}
		}
		$mysqli_result->free();
		return $result;
	}

	private function cast(&$row){
		foreach($row as $k => &$v){
			if(!is_null($v)){
				settype($v, $this->_('fields')[ $k ]);
			}
		}
		return $row;
	}
}