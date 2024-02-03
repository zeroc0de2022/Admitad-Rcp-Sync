<?php
declare(strict_types = 1);
/***
 * Date 07.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Mapper;

use Cpsync\Database;
use Cpsync\Mapper\Const\Notice;
use Cpsync\Session;
use Exception;

/**
 *
 */
class Login
{
	/**
	 * @var Database $database
	 */
	public Database $database;

	/**
	 * @var Session $session
	 */
	public Session $session;

	/**
	 * Constructor.
	 * @param Database $database
	 * @param Session $session
	 */
	public function __construct(Database $database, Session $session)
	{
		$this->database = $database;
		$this->session = $session;
	}

	/**
	 * User auth by login/password
	 * @param string $user_login
	 * @param string $user_pass
	 * @return bool
	 * @throws Exception
	 */
	public function loginPost(string $user_login, string $user_pass): bool
	{
		$user = $this->getUserData($user_login, $user_pass);
		if(!password_verify($user_pass, $user['user_pass'])) {
			throw new Exception(__LINE__.': '.Notice::W_INVALID_AUTH);
		}
		$this->session->setData('user', ['user_id' => $user['user_id'], 'user_name' => $user['user_name'], 'user_email' => $user['user_email'], 'user_login' => $user['user_login'], 'user_status' => $user['user_status']]);
		return true;
	}

	/**
	 * Get user data
	 * @param string $user_login
	 * @param string $user_pass
	 * @return mixed
	 * @throws Exception
	 */
	private function getUserData(string $user_login, string $user_pass): mixed
	{
		if(empty($user_login))
			throw new Exception(__LINE__.': '.Notice::W_EMPTY_LOGIN);
		if(empty($user_pass))
			throw new Exception(__LINE__.': '.Notice::W_EMPTY_PASSWORD);
		$connect = $this->database->getConnection()->prepare('SELECT * FROM users WHERE user_login = :user_login');
		$connect->execute([':user_login' => $user_login]);
		$user = $connect->fetch();
		if(empty($user)) {
			throw new Exception(__LINE__.': '.Notice::W_INVALID_AUTH);
		}
		return $user;
	}

	/**
	 * Log out
	 * @return void
	 */
	public function logout(): void
	{
		$this->session->setData('user', null);
	}

}