<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Email {
	use Shell;

	private $from = null;
	private $subject = null;
	private $message = null;

	private function __init(){
		$this->from = "no-reply@rha.enderteszla.su";
	}

	public function send($target,$template,$data){
		if(!method_exists($this,$template)){
			return $this->addError('email',0);
		}
		$headers = "From: {$this->from}\r\n";
		$headers .= "X-Mailer: PHP/" . phpversion();
		mail(
			$target,
			$this->$template($data)->subject,
			Output::_getInstance()
				->setSource(array_merge(array('subject' => $this->subject,'message' => $this->message),$data))
				->expose(VIEW_PATH . "email.php",true)
				->__(),
			$headers
		);
		return $this;
	}

	private function activate($data = array()){
		$this->subject = Lang::_getInstance()->getValue('activationSubject','User');
		$this->message = Lang::_getInstance()->getValue('activationBody','User',$data['token']);
		return $this;
	}
	private function restorePassword($data = array()){
		$this->subject = Lang::_getInstance()->getValue('passwordRestorationSubject','User');
		$this->message = Lang::_getInstance()->getValue('passwordRestorationBody','User',$data['token']);
		return $this;
	}
}