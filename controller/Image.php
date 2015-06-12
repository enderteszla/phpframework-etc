<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Image extends Content {
	/**
	 * @return $this
	 */
	protected function processSource(){
		$fileInfo = finfo_open(FILEINFO_MIME);
		if(!preg_match(':^image/(jpeg|png|gif);\ charset\=binary$:',finfo_file($fileInfo,$this->source['tmp_name']),$matches)){
			return $this->addError('image',0);
		}
		$ext = str_replace('jpeg','jpg',$matches[1]);
		list($this->source['w'],$this->source['h']) = getimagesize($this->source['tmp_name']);
		switch($ext){
			case 'jpg':
				$this->source['image'] = imagecreatefromjpeg($this->source['tmp_name']);
				unlink($this->source['tmp_name']);
				return $this;
			case 'png':
				$this->source['image'] = imagecreatefrompng($this->source['tmp_name']);
				break;
			case 'gif':
				$this->source['image'] = imagecreatefromgif($this->source['tmp_name']);
				break;
		}
		unlink($this->source['tmp_name']);
		imagealphablending($this->source['image'],false);
		imagesavealpha($this->source['image'],true);
		return $this;
	}

	/**
	 * @return $this
	 */
	protected function prepareFilter(){
		switch(true){
			case $this->filter['w'] == "*" && $this->filter['h'] == "*":
				$this->filter['w'] = $this->source['w'];
				$this->filter['h'] = $this->source['h'];
				break;
			case $this->filter['w'] == "*":
				$this->filter['w'] = round($this->filter['h'] * $this->source['w'] / $this->source['h']);
				break;
			case $this->filter['h'] == "*":
				$this->filter['h'] = round($this->filter['w'] * $this->source['h'] / $this->source['w']);
				break;
		}
		if($this->source['w'] / $this->filter['w'] - $this->source['h'] / $this->filter['h'] > 0){
			$this->filter['x'] = round(($this->source['w'] - $this->source['h'] * $this->filter['w'] / $this->filter['h']) / 2);
			$this->filter['y'] = 0;
			$this->filter['ws'] = round($this->source['h'] * $this->filter['w'] / $this->filter['h']);
			$this->filter['hs'] = $this->source['h'];
		} else {
			$this->filter['x'] = 0;
			$this->filter['y'] = round(($this->source['h'] - $this->source['w'] * $this->filter['h'] / $this->filter['w']) / 2);
			$this->filter['ws'] = $this->source['w'];
			$this->filter['hs'] = round($this->source['w'] * $this->filter['h'] / $this->filter['w']);
		}
		return $this;
	}

	/**
	 * @param int $id
	 * @return $this
	 */
	protected function save($id = 0){
		if($this->countErrors()){
			return $this;
		}
		if($id == 0) {
			$id = null;
			if ($this->_upsert(array(
				'Width' => $this->filter['w'],
				'Height' => $this->filter['h'],
				'URL' => ""
			))->countErrors()
			) {
				return $this;
			}
			$id = $this->_result['ID'];
		}
		if ($this->_upsert(array(
			'Width' => $this->filter['w'],
			'Height' => $this->filter['h'],
			'URL' => $this->path . "$id.{$this->filter['ext']}"
		), $id)->countErrors()
		) {
			return $this;
		}
		$this->_result['image'] = imagecreatetruecolor($this->filter['w'],$this->filter['h']);
		if($this->filter['alpha']){
			imagealphablending($this->_result['image'],false);
			imagesavealpha($this->_result['image'],true);
		}
		imagecopyresampled(
			$this->_result['image'],
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
				imagejpeg($this->_result['image'],BASE_PATH . $this->_result['URL']);
				break;
			case $this->filter['ext'] == 'png':
				imagepng($this->_result['image'],BASE_PATH . $this->_result['URL']);
				break;
		}
		imagedestroy($this->_result['image']);
		unset($this->_result['image']);
		return $this;
	}

	/**
	 * @return $this
	 */
	protected function finalize(){
		imagedestroy($this->source['image']);
		$this->source = null;
		return $this;
	}
}