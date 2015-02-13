<?php if(!defined('BASE_PATH')) include $_SERVER['DOCUMENT_ROOT'] . '/404.php';

require_once SHELL_PATH . 'PasswordHash.php';

class User {
	use Controller;

	public function index(){
		$this->result = array();
		return $this;
	}

	public function register(){
		if(!Input::getInstance()->getValue('json')){
			$this->result = array();
			return $this;
		}
		$email = Input::getInstance()->getValue('email');
		$password = Input::getInstance()->getValue('password');
		$firstName = Input::getInstance()->getValue('firstName');
		$lastName = Input::getInstance()->getValue('lastName');
		if(!empty($this->get($email,'Email')->result)){
			return $this->addError("Registration Error (0): User with specified email already exists");
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
		$subject = 'Активация учётной записи РосПатриот';
		$message = 'Для активации учётной записи, пожалуйста, пройдите по ссылке: http://rospatriot.enderteszla.su/user/activate/' . Token::getInstance()->getResult()['Content'];
		$headers = 'From: no-reply@rospatriot.enderteszla.su' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
		mail($email, $subject, $message, $headers);
		$this->result = 'Письмо, содержащее инструкции по активации учётной записи, отправлено на Ваш электронный адрес.';
		return $this;
	}
	public function login(){
		if(!Input::getInstance()->getValue('json')){
			include BASE_PATH . '/404.php';
		}
		$email = Input::getInstance()->getValue('email');
		$password = Input::getInstance()->getValue('password');
		if($this->get($email,'Email',true)->_errorsNumber || empty($this->result)){
			return $this->addError("Authentication Error (3): User not found");
		}
		if(!PasswordHash::getInstance()->validate_password($password,$this->result[0]['Password'])){
			return $this->addError("Authentication Error (4): Wrong password");
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
			return $this->addError("Authentication Error (0): Session already closed");
		}
		if(!$this->addErrors(Token::getInstance()->drop(Token::getInstance()->getResult()[0]['ID'])->errors())->_errorsNumber){
			// Здесь мы по идее должны сохранять корзину пользователя..?
		}
		return $this;
	}
	public function activate($content){
		if(!empty(Token::getInstance()->get(array(
				'Content' => $content,
				'Type' => 'activate'
			))->putResult($token)->errors()) || is_null($token)) {
			return $this->addError("Activation Error (0): Token doesn't exist");
		}
		if($this->set($token[0]['UserID'])->_errorsNumber){
			return $this;
		}
		$this->result = $this->get($token[0]['UserID']);
		return $this->addErrors(Token::getInstance()->drop($token[0]['ID'])->errors());
	}
	public function restorePassword($content = null){
		if(!is_null($content)){
			if(!empty(Token::getInstance()->get(array(
					'Content' => $content,
					'Type' => 'restorePassword'
				))->putResult($token)->errors()) || is_null($token)) {
				return $this->addError("Password Restoration Error (0): Token doesn't exist");
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
			$this->result = 'Пароль успешно обновлён.';
			return $this;
		}
		if(!Input::getInstance()->getValue('json')) {
			$this->result = array();
			return $this;
		}
		$email = Input::getInstance()->getValue('email');
		if($this->get($email,'Email',true)->_errorsNumber || empty($this->result)){
			return $this->addError("Password Restoration Error (1): User not found");
		}
		if($this->addErrors(Token::getInstance()->upsert(array(
			'Content' => Token::getInstance()->generate(),
			'Type' => 'restorePassword',
			'UserID' => $this->result['ID']
		))->errors())->_errorsNumber){
			return $this;
		}
		$subject = 'Восстановление пароля учётной записи РосПатриот';
		$message = 'Для восстановления пароля учётной записи, пожалуйста, пройдите по ссылке: http://rospatriot.enderteszla.su/user/restorePassword/' . Token::getInstance()->getResult()['Content'];
		$headers = 'From: no-reply@rospatriot.enderteszla.su' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
		mail($email, $subject, $message, $headers);
		$this->result = 'Письмо, содержащее инструкции по восстановлению доступа, отправлено на Ваш электронный адрес.';
		return $this;
	}
}