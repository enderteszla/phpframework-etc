<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Image {
	use Controller;

	private $source = null;
	private $filter = null;

	public function create($id = 0,$finalize = true){
		Config::getInstance()->load('Image');
		$filter = explode('/',input('filter'));
		switch(true){
			case empty($filter):
			case count($filter) == 1 && !array_key_exists($filter[0],config('filters','Image')):
				$filter = config('filters','Image')['Default'];
				$path = config('contentPath','Image') . "default/";
				break;
			case count($filter) == 1:
				$filter = config('filters','Image')[input('filter')];
				$path = config('contentPath','Image') . lcfirst(input('filter')) . "/";
				break;
			default:
				$filter = config('filters','Image');
				$path = config('contentPath','Image');
				foreach(explode('/',input('filter')) as $i){
					if(array_key_exists($i,$filter)){
						$filter = $filter[$i];
						$path .= lcfirst($i) . "/";
					} else {
						$filter = config('filters','Image')['Default'];
						$path = config('contentPath','Image') . "default/";
						break;
					}
				}
		}
		return $this->processSource()->prepareFilter($filter)->save($path,$id)->finalize($finalize);
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
		if($this->_get($ids)->_drop()->_errorsNumber){
			return $this;
		}
		if(is_assoc($this->result)){
			@unlink(BASE_PATH . $this->result['URL']);
		} else {
			foreach($this->result as $image){
				@unlink(BASE_PATH . $image['URL']);
			}
		}
		return $this;
	}

	private function processSource(){
		if(!is_null($this->source)){
			return $this;
		}
		$tmp_name = $_FILES[config('uploadFileIndex','Default')]['tmp_name'];
		$this->source = array();
		if(!is_uploaded_file($tmp_name)){
			return $this->addError('image',0);
		}
		$fileInfo = finfo_open(FILEINFO_MIME);
		if(!preg_match(':^image/(jpeg|png|gif);\ charset\=binary$:',finfo_file($fileInfo,$tmp_name),$matches)){
			return $this->addError('image',1);
		}
		$ext = str_replace('jpeg','jpg',$matches[1]);
		list($this->source['w'],$this->source['h']) = getimagesize($tmp_name);
		switch($ext){
			case 'jpg':
				$this->source['image'] = imagecreatefromjpeg($tmp_name);
				unlink($tmp_name);
				return $this;
			case 'png':
				$this->source['image'] = imagecreatefrompng($tmp_name);
				break;
			case 'gif':
				$this->source['image'] = imagecreatefromgif($tmp_name);
				break;
		}
		unlink($tmp_name);
		imagealphablending($this->source['image'],false);
		imagesavealpha($this->source['image'],true);
		return $this;
	}
	private function prepareFilter($filter){
		$this->filter = $filter;
		switch(true){
			case $this->filter['w'] == "*" && $this->filter['h'] == "*":
				$this->filter['w'] = $this->source['w'];
				$this->filter['h'] = $this->source['h'];
				break;
			case $this->filter['w'] == "*":
				$this->filter['w'] = $this->filter['h'] * $this->source['w'] / $this->source['h'];
				break;
			case $this->filter['h'] == "*":
				$this->filter['h'] = $this->filter['w'] * $this->source['h'] / $this->source['w'];
				break;
		}
		if($this->source['w'] / $this->filter['w'] - $this->source['h'] / $this->filter['h'] > 0){
			$this->filter['x'] = round(($this->source['w'] - $this->source['h'] * $this->filter['w'] / $this->filter['h']) / 2);
			$this->filter['y'] = 0;
			$this->filter['ws'] = $this->source['h'] * $this->filter['w'] / $this->filter['h'];
			$this->filter['hs'] = $this->source['h'];
		} else {
			$this->filter['x'] = 0;
			$this->filter['y'] = round(($this->source['h'] - $this->source['w'] * $this->filter['h'] / $this->filter['w']) / 2);
			$this->filter['ws'] = $this->source['w'];
			$this->filter['hs'] = $this->source['w'] * $this->filter['h'] / $this->filter['w'];
		}
		return $this;
	}
	private function save($folder, $id = 0){
		if($this->_errorsNumber){
			return $this;
		}
		if($id == 0) {
			$id = null;
			if ($this->_upsert(array(
				'Width' => $this->filter['w'],
				'Height' => $this->filter['h'],
				'URL' => ""
			))->_errorsNumber
			) {
				return $this;
			}
			$id = $this->result['ID'];
		}
		if ($this->_upsert(array(
			'Width' => $this->filter['w'],
			'Height' => $this->filter['h'],
			'URL' => $folder . "$id.{$this->filter['ext']}"
		), $id)->_errorsNumber
		) {
			return $this;
		}
		$this->result['image'] = imagecreatetruecolor($this->filter['w'],$this->filter['h']);
		if($this->filter['alpha']){
			imagealphablending($this->result['image'],false);
			imagesavealpha($this->result['image'],true);
		}
		imagecopyresampled(
			$this->result['image'],
			$this->source['image'],
			0,
			0,
			$this->filter['x'],
			$this->filter['y'],
			$this->filter['w'],
			$this->filter['h'],
			$this->filter['ws'],
			$this->filter['hs']
		);
		switch(true){
			case $this->filter['ext'] == 'jpg':
				imagejpeg($this->result['image'],BASE_PATH . $this->result['URL']);
				break;
			case $this->filter['ext'] == 'png':
				imagepng($this->result['image'],BASE_PATH . $this->result['URL']);
				break;
		}
		imagedestroy($this->result['image']);
		unset($this->result['image']);
		return $this;
	}
	private function finalize($finalize){
		if($finalize) {
			imagedestroy($this->source['image']);
			$this->source = null;
		}
		return $this;
	}
}