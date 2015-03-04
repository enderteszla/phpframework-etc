<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Image {
	use Controller;

	private $config = null;
	private $source = null;
	private $filter = null;

	private function __init(){
		$Image = array();
		include_once CONFIG_PATH . 'Image.php';
		$this->config = $Image;
	}

	public function create($owner_type = 'default',$id = 0,$finalize = true){
		if(!in_array($owner_type,array_keys($this->config['filters']))){
			$owner_type = 'default';
		}
		return $this
			->processSource()
			->prepareFilter($this->config['filters'][$owner_type])
			->save($this->config['contentPath'] . "$owner_type/",$id)
			->finalize($finalize);
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
		if($this->addErrors(DB::getInstance()->get('Image',false,$ids)->putResult($image)->errors())->_errorsNumber){
			return $this;
		}
		if($this->addErrors(DB::getInstance()->drop('Image',$ids)->errors())->_errorsNumber){
			return $this;
		}
		@unlink(BASE_PATH . $image['URL']);
		return $this;
	}

	private function processSource(){
		if(!is_null($this->source)){
			return $this;
		}
		$tmp_name = $_FILES[$this->config['file_index']]['tmp_name'];
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
			if ($this->addErrors(DB::getInstance()->upsert('Image', false, array(
				'Width' => $this->filter['w'],
				'Height' => $this->filter['h'],
				'URL' => $folder . ((int)(array_reverse(DB::getInstance()->get('Image')->getResult())[0]['ID']) + 1) . ".{$this->filter['ext']}"
			))->putResult($target)->errors())->_errorsNumber
			) {
				return $this;
			}
		} else {
			if ($this->addErrors(DB::getInstance()->get('Image', false, $id)->putResult($target)->errors())->_errorsNumber){
				return $this;
			}
		}
		$target['image'] = imagecreatetruecolor($this->filter['w'],$this->filter['h']);
		if($this->filter['alpha']){
			imagealphablending($target['image'],false);
			imagesavealpha($target['image'],true);
		}
		imagecopyresampled(
			$target['image'],
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
				imagejpeg($target['image'],BASE_PATH . $target['URL']);
				break;
			case $this->filter['ext'] == 'png':
				imagepng($target['image'],BASE_PATH . $target['URL']);
				break;
		}
		imagedestroy($target['image']);
		unset($target['image']);
		return $this->setResult($target);
	}

	private function finalize($finalize){
		if($finalize) {
			imagedestroy($this->source['image']);
			$this->source = null;
		}
		return $this;
	}
}