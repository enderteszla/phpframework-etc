<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

require_once SHELL_PATH . 'Email.php';
require_once SHELL_PATH . 'PasswordHash.php';

class User {
	use Controller;

	public function index(){
		return $this->setResult(array());
	}
	public function register(){
		Lang::getInstance()->load('User');
		if(!Input::getInstance()->getValue('json')){
			$this->result = array();
			return $this;
		}
		$email = Input::getInstance()->getValue('email');
		$password = Input::getInstance()->getValue('password');
		$firstName = Input::getInstance()->getValue('firstName');
		$lastName = Input::getInstance()->getValue('lastName');
		if(!empty($this->get($email,'Email')->result)){
			return $this->addError('registration',0);
		}
		if($this->upsert(array(
			'Email' => $email,
			'Password' => PasswordHash::getInstance()->create_hash($password),
			'FirstName' => $firstName,
			'LastName' => $lastName
		))->_errorsNumber) {
			return $this;
		}
		if($this->addErrors(Token::getInstance()->upsert(array(
			'Content' => Token::getInstance()->generate(),
			'Type' => 'activate',
			'UserID' => $this->result['ID']
		))->errors())->_errorsNumber){
			return $this;
		}
		if(!$this->addErrors(Email::getInstance()
			->send($email,'activate',array('token' => array(Token::getInstance()->getResult()['Content'])))
			->errors())->_errorsNumber){
			$this->result = Lang::getInstance()->getValue('activationSent','User');
		}
		return $this;
	}
	public function login(){
		if(!Input::getInstance()->getValue('json')){
			include BASE_PATH . '/404.php';
		}
		$email = Input::getInstance()->getValue('email');
		$password = Input::getInstance()->getValue('password');
		if($this->get($email,'Email',true)->_errorsNumber || empty($this->result)){
			return $this->addError('authentication',3);
		}
		if(!PasswordHash::getInstance()->validate_password($password,$this->result[0]['Password'])){
			return $this->addError('authentication',4);
		}
		if(!$this->addErrors(Token::getInstance()->upsert(array(
			'Content' => Token::getInstance()->generate(),
			'Type' => 'session',
			'UserID' => $this->result[0]['ID']
		))->putResult($token)->errors())->_errorsNumber){
			$this->result = $token['Content'];
		}
		return $this;
	}
	public function logout(){
		if(!Input::getInstance()->getValue('json')){
			include BASE_PATH . '/404.php';
		}
		if(is_null(Token::getInstance()->getResult()[0]['UserID']) || (Token::getInstance()->getResult()[0]['Type'] != 'session')) {
			return $this->addError('authentication',2);
		}
		if(!$this->addErrors(Token::getInstance()->drop(Token::getInstance()->getResult()[0]['ID'])->errors())->_errorsNumber){
			// Anything else? Yeah. Sue me.
		}
		return $this;
	}
	public function activate($content){
		if(!empty(Token::getInstance()->get(array(
				'Content' => $content,
				'Type' => 'activate'
			))->putResult($token)->errors()) || is_null($token)) {
			return $this->addError('activation',0);
		}
		if($this->set($token[0]['UserID'])->_errorsNumber){
			return $this;
		}
		$this->result = $this->get($token[0]['UserID']);
		return $this->addErrors(Token::getInstance()->drop($token[0]['ID'])->errors());
	}
	public function restorePassword($content = null){
		Lang::getInstance()->load('User');
		if(!is_null($content)){
			if(!empty(Token::getInstance()->get(array(
					'Content' => $content,
					'Type' => 'restorePassword'
				))->putResult($token)->errors()) || is_null($token)) {
				return $this->addError('password restoration',0);
			}
			$this->get(Token::getInstance()->getResult()[0]['UserID']);
			if(!Input::getInstance()->getValue('json')) {
				return $this;
			}
			if($this->upsert(array(
				'Email' => $this->result['Email'],
				'Password' => PasswordHash::getInstance()->create_hash(Input::getInstance()->getValue('password')),
				'FirstName' => $this->result['FirstName'],
				'LastName' => $this->result['LastName']
			))->_errorsNumber){
				return $this;
			}
			$this->addErrors(Token::getInstance()->drop($token[0]['ID'])->errors())->result = Lang::getInstance()->getValue('passwordReset','User');
			return $this;
		}
		if(!Input::getInstance()->getValue('json')) {
			$this->result = array();
			return $this;
		}
		$email = Input::getInstance()->getValue('email');
		if($this->get($email,'Email',true)->_errorsNumber || empty($this->result)){
			return $this->addError('password restoration',1);
		}
		if($this->addErrors(Token::getInstance()->upsert(array(
			'Content' => Token::getInstance()->generate(),
			'Type' => 'restorePassword',
			'UserID' => $this->result['ID']
		))->errors())->_errorsNumber){
			return $this;
		}
		if(!$this->addErrors(Email::getInstance()
			->send($email,'restorePassword',array('token' => array(Token::getInstance()->getResult()['Content'])))
			->errors())->_errorsNumber){
			$this->result = Lang::getInstance()->getValue('passwordRestorationSent','User');
		}
		return $this;
	}
}