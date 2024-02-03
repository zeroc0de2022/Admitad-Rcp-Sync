<?php
declare(strict_types = 1);
/***
 * Date 25.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Mapper;

use Cpsync\Database;
use Cpsync\Mapper\Const\Notice;
use Cpsync\Mapper\Trait\Validator;
use Cpsync\Session;
use Exception;

/**
 * Class Profile
 */
class Profile
{
	use Validator;

	/**
	 * @var Database $database
	 */
	public Database $database;

	/**
	 * @var mixed|null
	 */
	private array $userSession;

	/**
	 * @var Session
	 */
	public Session $session;

	/**
	 * Profile constructor.
	 * @param Database $database
	 * @param Session $session
	 */
	public function __construct(Database $database, Session $session)
	{
		$this->session = $session;
		$this->userSession = $this->session->getData('user');
		$this->database = $database;
	}

	/**
	 * Update user profile
	 * @param array $params
	 * @param string $password
	 * @return void
	 * @throws \Exception
	 */
	public function updateUserProfile(array $params, string $password): void
	{
		$connect = $this->database->getConnection()->prepare('UPDATE users SET user_email = :email, user_name = :user_name, user_pass = :user_pass WHERE user_login = :user_login');
		$connect->execute([':email' => $params['user_email'], ':user_name' => $params['user_name'], ':user_pass' => $password, ':user_login' => $this->userSession['user_login'],]);
		if($connect->rowCount()) {
			$this->updateUserSession($params['user_name'], $params['user_email']);
			throw new Exception(__LINE__.': '.Notice::N_PROFILE_UPDATED);
		}
		throw new Exception(__LINE__.': '.Notice::E_PROFILE_UPDATE);
	}

	/**
	 * Check user authorization
	 * @param string $user_login
	 * @param string $user_pass
	 * @return void
	 * @throws \Exception
	 */
	public function checkUserAuth(string $user_login, string $user_pass): void
	{
		$result = $this->database->getConnection()->prepare('SELECT * FROM users WHERE user_login=:user_login');
		$result->execute([':user_login' => $user_login]);
		$user = $result->fetch();
		if(empty($user)) {
			throw new Exception(__LINE__.': '.Notice::E_USER_NOT_FOUND);
		}
		$this->checkUserPassword($user, $user_pass);
	}

	/**
	 * Check user password validity
	 * @param array $user - user data like [user_login, user_pass] from database
	 * @param string $user_pass - user password like '123456' from form
	 * @throws \Exception
	 */
	public function checkUserPassword(array $user, string $user_pass): void
	{
		if((!count($user) || !password_verify($user_pass, $user['user_pass']))) {
			throw new Exception(__LINE__.': '.Notice::W_INVALID_PASSWORD);
		}
		$this->setUserSession($user);
	}

	/**
	 * Check password validity
	 * @param array $user
	 */
	public function setUserSession(array $user): void
	{
		$user_data = [];
		foreach($user as $key => $value) {
			if($key === 'user_pass'){
				continue;
			}
			$user_data[$key] = $value;
		}
		$this->session->setData('user', $user_data);
	}

	/**
	 * Check new user data for validity
	 * @throws Exception
	 */
	private function isValidNewUserData(&$params): void
	{
		if(!$this->isNameValid($params['user_name'])) {
			throw new Exception(__LINE__.': '.Notice::W_NAME_ONLY_LETTERS);
		}
		if(!$this->isSizeValid($params['user_name'])){
			throw new Exception(__LINE__.': '.Notice::W_NAME_NOT_EXCEED_15);
		}
		if(!$this->isEmailValid($params['user_email'])){
			throw new Exception(__LINE__.': '.Notice::W_INVALID_EMAIL);
		}
		// Check new password if it exists
		if(!empty($params['password1']) && !empty($params['password2'])) {
			if($params['password1'] !== $params['password2']) {
				throw new Exception(__LINE__.': '.Notice::W_PASSWORD_NOT_MATCH);
			}
			$params['new_pass'] = password_hash($params['password1'], PASSWORD_BCRYPT);
		}
	}

	/**
	 * Update user session data
	 * @param string $userName
	 * @param string $userEmail
	 * @return void
	 */
	private function updateUserSession(string $userName, string $userEmail): void
	{
		$this->session->setData('user', ['user_id' => $this->userSession['user_id'], 'user_name' => $userName, 'user_email' => $userEmail, 'user_login' => $this->userSession['user_login'], 'user_status' => $this->userSession['user_status'],]);
	}

	/**
	 * Get user profile
	 * @param string $user_login
	 * @return array
	 * @throws Exception
	 */
	public function getProfile(string $user_login): array
	{
		$connect = $this->database->getConnection()->prepare('SELECT * FROM users WHERE user_login = :user_login');
		$result = $connect->execute([':user_login' => $user_login]);
		if(!$result)
			throw new Exception(__LINE__.': '.Notice::E_GET_PROFILE);
		$result = $connect->fetchAll();
		return array_shift($result);
	}

	/**
	 * Update user profile
	 * @param array $params
	 * @return void
	 * @throws \Exception
	 */
	public function updateProfile(array $params): void
	{
		$this->checkInputKey($params);
		if(!isset($params['action']) || $params['action'] !== 'profile') {
			throw new Exception(__LINE__.': '.Notice::E_INVALID_ACTION);
		}
		$this->checkUserAuth($this->userSession['user_login'], $params['password']);
		// Check new user data for validity
		$this->isValidNewUserData($params);
		$password = $params['new_pass'] ?? password_hash($params['password'], PASSWORD_BCRYPT);
		try {
			$this->updateUserProfile($params, $password);
		} catch(Exception $exception) {
			throw new Exception($exception->getMessage(), 500);
		}
	}


}