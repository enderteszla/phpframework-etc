<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class Email extends Shell {
	/**
	 * @var string
	 */
	private $from = null;
	/**
	 * @var string
	 */
	private $subject = null;
	/**
	 * @var string
	 */
	private $message = null;

	protected function __init(){
		$this->from = "no-reply@rha.enderteszla.su";
	}

	/**
	 * @param string $target
	 * @param string $template
	 * @param array $data
	 * @return $this
	 */
	public function send($target,$template,$data){
		if(!method_exists($this,$template)){
			return $this->addError('email',0);
		}
		$headers = "From: {$this->from}\r\n";
		$headers .= "X-Mailer: PHP/" . phpversion();
		$json = input('json');
		input('json',0);
		mail(
			$target,
			$this->$template($data)->subject,
			/* Output::_getInstance()
				->setSource(array_merge(array('subject' => $this->subject,'message' => $this->message),$data))
				->expose(VIEW_PATH . "email.php",true)
				->__(), */
			$this->message,
			$headers
		);
		input('json',$json);
		return $this;
	}

	/**
	 * @param array $data
	 * @return $this
	 */
	private function activate($data = array()){
		$this->subject = Lang::_getInstance()->getValue('activationSubject','User');
		$this->message = Lang::_getInstance()->getValue('activationBody','User',array($data['token']));
		return $this;
	}

	/**
	 * @param array $data
	 * @return $this
	 */
	private function restorePassword($data = array()){
		$this->subject = Lang::_getInstance()->getValue('passwordRestorationSubject','User');
		$this->message = Lang::_getInstance()->getValue('passwordRestorationBody','User',array($data['token']));
		return $this;
	}
}