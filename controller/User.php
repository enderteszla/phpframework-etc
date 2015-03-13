<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

require_once SHELL_PATH . 'Email.php';
require_once SHELL_PATH . 'PasswordHash.php';

class User {
	use Controller;

	public function index(){
		return $this->setResult(array());
	}
	public function register(){
		if(!input('json')){
			$this->result = array();
			return $this;
		}
		$email = input('email');
		$password = input('password');
		$firstName = input('firstName');
		$lastName = input('lastName');
		if(!empty($this->_get($email,'Email')->result)){
			return $this->addError('registration',0);
		}
		if($this->_upsert(array(
			'Email' => $email,
			'Password' => PasswordHash::getInstance()->create_hash($password),
			'FirstName' => $firstName,
			'LastName' => $lastName
		))->_errorsNumber) {
			return $this;
		}
		if($this->addErrors(Token::getInstance()->_upsert(array(
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
		if(!input('json')){
			include BASE_PATH . '/404.php';
		}
		$email = input('email');
		$password = input('password');
		if($this->_get($email,'Email',true)->_errorsNumber || empty($this->result)){
			return $this->addError('authentication',3);
		}
		if(!PasswordHash::getInstance()->validate_password($password,$this->result[0]['Password'])){
			return $this->addError('authentication',4);
		}
		if(!$this->addErrors(Token::getInstance()->_upsert(array(
			'Content' => Token::getInstance()->generate(),
			'Type' => 'session',
			'UserID' => $this->result[0]['ID']
		))->putResult($token)->errors())->_errorsNumber){
			$this->result = $token['Content'];
		}
		return $this;
	}
	public function logout(){
		if(!input('json')){
			include BASE_PATH . '/404.php';
		}
		if(is_null(Token::getInstance()->getResult()[0]['UserID']) || (Token::getInstance()->getResult()[0]['Type'] != 'session')) {
			return $this->addError('authentication',2);
		}
		if(!$this->addErrors(Token::getInstance()->_drop(Token::getInstance()->getResult()[0]['ID'])->errors())->_errorsNumber){
			// Anything else? Yeah. Sue me.
		}
		return $this;
	}
	public function activate($content){
		if(!empty(Token::getInstance()->_get(array(
				'Content' => $content,
				'Type' => 'activate'
			))->putResult($token)->errors()) || is_null($token)) {
			return $this->addError('activation',0);
		}
		if($this->_set($token[0]['UserID'])->_errorsNumber){
			return $this;
		}
		$this->result = $this->_get($token[0]['UserID']);
		return $this->addErrors(Token::getInstance()->_drop($token[0]['ID'])->errors());
	}
	public function restorePassword($content = null){
		if(!is_null($content)){
			if(!empty(Token::getInstance()->_get(array(
					'Content' => $content,
					'Type' => 'restorePassword'
				))->putResult($token)->errors()) || is_null($token)) {
				return $this->addError('password restoration',0);
			}
			$this->_get(Token::getInstance()->getResult()[0]['UserID']);
			if(!input('json')) {
				return $this;
			}
			if($this->_upsert(array(
				'Email' => $this->result['Email'],
				'Password' => PasswordHash::getInstance()->create_hash(input('password')),
				'FirstName' => $this->result['FirstName'],
				'LastName' => $this->result['LastName']
			))->_errorsNumber){
				return $this;
			}
			$this->addErrors(Token::getInstance()->_drop($token[0]['ID'])->errors())->result = Lang::getInstance()->getValue('passwordReset','User');
			return $this;
		}
		if(!input('json')) {
			$this->result = array();
			return $this;
		}
		$email = input('email');
		if($this->_get($email,'Email',true)->_errorsNumber || empty($this->result)){
			return $this->addError('password restoration',1);
		}
		if($this->addErrors(Token::getInstance()->_upsert(array(
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