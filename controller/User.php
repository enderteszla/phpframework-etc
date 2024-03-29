<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

class User extends Controller {
	/**
	 * @return $this
	 */
	public function index(){
		return $this->result(array());
	}

	/**
	 * @return $this
	 */
	public function register(){
		if(!input('json')){
			return $this->result(array());
		}
		$email = input('email');
		$password = input('password');
		$firstName = input('firstName');
		$lastName = input('lastName');
		if(!empty($this->_get($email,'Email')->_result)){
			return $this->addError('registration',0);
		}
		if($this->_upsert(array(
			'Email' => $email,
			'Password' => PasswordHash::_getInstance()->create_hash($password),
			'FirstName' => $firstName,
			'LastName' => $lastName,
			'RoleID' => null
		))->countErrors()){
			return $this;
		}
		if(config('accountActivation','Default')){
			Token::_getInstance()->_upsert(array(
				'Content' => Token::_getInstance()->_generate(),
				'Type' => 'activate',
				'UserID' => $this->_result['ID']
			))->__($this->_('token',false));
			Email::_getInstance()
				->send($email,'activate',array('token' => $this->_('token')['Content']));
			if($this->countErrors()) {
				Token::_getInstance()->_drop();
				return $this->_drop();
			}
			return $this->result(Lang::_getInstance()->getValue('activationSent','User'));
		} else {
			return $this->_set($this->_result['ID']);
		}
	}

	/**
	 * @return $this
	 */
	public function login(){
		if(!input('json')){
			return $this->result(array());
		}
		$email = input('email');
		$password = input('password');
		if($this->_get(array('Email' => $email,'Active' => true))->_eq()->countErrors() || empty($this->_result)){
			return $this->addError('authentication',3);
		}
		if(!PasswordHash::_getInstance()->validate_password($password,$this->_result['Password'])){
			return $this->addError('authentication',4);
		}
		Token::_getInstance()->_upsert(array(
			'Content' => Token::_getInstance()->_generate(),
			'Type' => 'session',
			'UserID' => $this->_result['ID']
		))->__($this->_('token',false));
		if($this->countErrors()){
			return $this;
		}
		setcookie('token',$this->_('token')['Content'],null,'/');
		return $this->result(array('Token' => $this->_('token')['Content']));
	}

	/**
	 * @return $this
	 */
	public function logout(){
		if(!input('json')){
			return error404();
		}
		Token::_getInstance()->__($this->_('token',false));
		if(is_null($this->_('token')) || is_null($this->_('token')['UserID']) || ($this->_('token')['Type'] != 'session')) {
			return $this->addError('authentication',2);
		}
		Token::_getInstance()->_drop();
		return $this;
	}

	/**
	 * @param string $content
	 * @return $this
	 */
	public function activate($content){
		Token::_getInstance()->_get(array(
			'Content' => $content,
			'Type' => 'activate'
		))->_eq()->_drop()->__($this->_('token',false));
		if($this->countErrors() || is_null($this->_('token'))) {
			return $this->addError('activation',0);
		}
		if($this->_set($this->_('token')['UserID'])->countErrors()){
			return $this;
		}
		return $this->_get($this->_('token')['UserID']);
	}

	/**
	 * @param string $content
	 * @return $this
	 */
	public function restorePassword($content = null){
		$t = Token::_getInstance();
		if(!is_null($content)){
			$t->_get(array(
				'Content' => $content,
				'Type' => 'restorePassword'
			))->_eq()->__($this->_('token',false));
			if($this->countErrors() || is_null($this->_('token'))) {
				return $this->addError('password restoration',0);
			}
			$this->_get($this->_('token')['UserID']);
			if(!input('json')) {
				return $this;
			}
			if($this->_upsert(array(
				'Email' => $this->_result['Email'],
				'Password' => PasswordHash::_getInstance()->create_hash(input('password')),
				'FirstName' => $this->_result['FirstName'],
				'LastName' => $this->_result['LastName'],
				'RoleID' => $this->_result['RoleID']
			))->countErrors()){
				return $this;
			}
			$t->_drop();
			return $this->result(Lang::_getInstance()->getValue('passwordReset','User'));
		}
		if(!input('json')) {
			return $this->result(array());
		}
		$email = input('email');
		if($this->_get(array('Email' => $email,'Active' => true))->_eq()->countErrors() || empty($this->_result)){
			return $this->addError('password restoration',1);
		}
		$t->_upsert(array(
			'Content' => $t->_generate(),
			'Type' => 'restorePassword',
			'UserID' => $this->_result['ID']
		))->__($this->_('token',false));
		Email::_getInstance()
			->send($email,'restorePassword',array('token' => $this->_('token')['Content']));
		if($this->countErrors()){
			$t->_drop();
			return $this;
		}
		return $this->result(Lang::_getInstance()->getValue('passwordRestorationSent','User'));
	}
}