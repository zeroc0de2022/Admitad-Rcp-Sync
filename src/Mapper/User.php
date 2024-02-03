<?php
declare(strict_types = 1);
/***
 * Date 22.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Mapper;

use Cpsync\Database;
use Cpsync\Mapper\Const\Notice;
use Cpsync\Mapper\Trait\Message;
use Cpsync\Session;
use Exception;

/**
 * Class User
 */
class User
{
	use Message;

	/**
	 * @var Session $session
	 */
	public Session $session;

	/**
	 * @var Database $database
	 */
	public Database $database;

	/**
	 * @var mixed $userSession
	 */
	public mixed $userSession;

	/**
	 * User constructor.
	 * @param Database $database
	 * @param Session $session
	 * @throws \Exception
	 */
	public function __construct(Database $database, Session $session)
	{
		$this->session = $session;
		$this->userSession = $this->session->getData('user');
		$this->database = $database;
	}

	/**
	 * Get all users data
	 * @throws Exception
	 */
	public function getUsers(): array
	{
		$request = $this->database->getConnection()->prepare('SELECT * FROM users');
		$result = $request->execute();
		if($result) {
			return $request->fetchAll();
		}
		$this->setMessage(Notice::E_GET_USERS);
		throw new Exception($this->getMessageJson());
	}

	/**
	 * Delete user
	 * @param int $user_id
	 * @throws \Exception
	 */
	public function deleteUser(int $user_id): void
	{
		$connect = $this->database->getConnection()->prepare('DELETE FROM users WHERE user_id=:user_id');
		$result = $connect->execute([':user_id' => $user_id]);
		(!$result) ? $this->setMessage(Notice::E_NO_USER_DELETE) : $this->setMessage(Notice::N_USER_DELETED, true);
		throw new Exception($this->getMessageJson());
	}

	/**
	 * Edit user
	 * @param int $user_id
	 * @param string $column
	 * @param string $value
	 * @throws \Exception
	 */
	public function editUser(int $user_id, string $column, string $value): void
	{
		$connect = $this->database->getConnection()->prepare('UPDATE users SET '.$column.'=:value WHERE user_id=:user_id');
		$result = $connect->execute([':value' => $value, ':user_id' => $user_id]);
		if(!$result) {
			$this->setMessage(Notice::E_NO_USER_UPDATE, true);
			throw new Exception($this->getMessageJson());
		}
	}

	/**
	 * Add new user
	 * @param string $user_status
	 * @param string $user_name
	 * @param string $user_login
	 * @param string $user_email
	 * @param string $user_pass
	 * @return bool
	 * @throws Exception
	 */
	public function newUser(string $user_status, string $user_name, string $user_login, string $user_email, string $user_pass): bool
	{
		$connect = $this->database->getConnection()->prepare('INSERT INTO users (user_status, user_name, user_login, user_email, user_pass) VALUES (:user_status,:user_name,:user_login,:user_email,:user_pass)');
		$result = $connect->execute([':user_status' => $user_status, ':user_name' => $user_name, ':user_login' => $user_login, ':user_email' => $user_email, ':user_pass' => $user_pass]);
		if(!$result) {
			$this->setMessage(Notice::E_ADD_USER);
			throw new Exception($this->getMessageJson());
		}
		return true;
	}

	/**
	 * Get user
	 * @param string $column
	 * @param string $value
	 * @return mixed
	 * @throws \Exception
	 */
	public function getUser(string $column, string $value): mixed
	{
		$connect = $this->database->getConnection()->prepare("SELECT * FROM users WHERE $column=:value");
		$result = $connect->execute([':value' => $value]);
		if(!$result) {
			$this->setMessage(Notice::E_GET_NO_USER);
			throw new Exception($this->getMessageJson());
		}
		return $connect->fetch();
	}

	/**
	 * Get array with access list
	 * @return array
	 * @throws \Exception
	 */
	public function getAccess(): array
	{
		$access = [];
		$request = $this->database->getConnection()->prepare('SELECT * FROM access');
		$request->execute();
		while($row = $request->fetch()) {
			$access[$row['access']] = json_decode($row['values']);
		}
		if(!count($access)) {
			$this->setMessage(Notice::E_NO_ACCESS);
			throw new Exception($this->getMessageJson());
		}
		return $access;
	}


}