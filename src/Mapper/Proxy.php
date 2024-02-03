<?php
declare(strict_types = 1);
/***
 * Date 20.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Mapper;

use Cpsync\Database;
use Cpsync\Mapper\Const\Notice;
use Cpsync\Mapper\Trait\Message;
use Exception;
use PDO;
use Cpsync\Session;
use Twig\Environment;

/**
 * Class Proxy
 */
class Proxy
{
	use Message;

	/**
	 * @var Database $database
	 */
	public Database $database;

	/**
	 * @var Session $session
	 */
	public Session $session;

	/**
	 * @var Environment $view
	 */
	public Environment $view;

	/**
	 * Proxy constructor
	 * @throws Exception
	 */
	public function __construct(Database $database, Session $session, Environment $view)
	{
		$this->database = $database;
		$this->session = $session;
		$this->view = $view;
	}

	/**
	 * Remove proxy
	 * @param array $post
	 * @return void
	 * @throws \Exception
	 */
	public function deleteProxy(array $post): void
	{
		$this->message['status'] = false;
		$action = (string)$post['action'];
		$values = (array)$post['values'];
		if($action === 'prodel' && count($values)) {
			$this->deleteProxyQuery($values);
		} else {
			$this->setMessage(Notice::W_INVALID_PARAM_VAL);
			throw new Exception($this->getMessageJson());
		}
	}

	/**
	 * Delete proxy request
	 * @param array $values - proxies id
	 * @return void
	 * @throws \Exception
	 */
	private function deleteProxyQuery(array $values): void
	{
		$id_query = $params = [];
		foreach($values as $index => $value) {
			if(is_numeric($value)) {
				$id_query[] = ':proxy_id_'.$index;
				$params[':proxy_id_'.$index] = $value;
			}
		}
		if(count($id_query) > 0) {
			$placeholders = implode(', ', $id_query);
			$query = "DELETE FROM proxy WHERE proxy_id IN ($placeholders)";
			$connect = $this->database->getConnection()->prepare($query);
			$connect->execute($params);
			// Check the success of the request
			($connect->rowCount()) ? $this->setMessage(Notice::N_PROXIES_DELETED, true) : $this->setMessage(Notice::W_NO_PROXIES_DELETED);
			throw new Exception($this->getMessageJson());
		}
		$this->setMessage(Notice::W_NO_PROXY_TO_DELETE);
		throw new Exception($this->getMessageJson());
	}

	/**
	 * Add proxy list to database.
	 * @param $proxyList - list of proxies
	 * @return void
	 * @throws Exception
	 */
	public function addProxyList($proxyList): void
	{
		$proxies = explode('\n', $proxyList);
		$pattern = '#^(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})(:\d{2,7})?(:[a-zA-Z0-9]*)?(:[a-zA-Z0-9]*)?$#';
		$time = time();
		$connect = $this->database->getConnection()->prepare("INSERT IGNORE INTO proxy(proxy, type, uptime) VALUES (:proxy, 'HTTP', :uptime)");

		foreach($proxies as $proxy) {
			$proxy = trim($proxy);
			if(strlen($proxy) > 20 && preg_match($pattern, $proxy)) {
				$connect->bindParam(':proxy', $proxy);
				$connect->bindParam(':uptime', $time, PDO::PARAM_INT);
				if(!$connect->execute()) {
					$this->setMessage(Notice::E_ADD_PROXY);
					throw new Exception($this->getMessageJson());
				}
			}
		}
	}

	/**
	 * Get proxy list
	 * @param int|null $status
	 * @return array|bool
	 */
	public function getProxyList(int $status = null): array|bool
	{
		$query = 'SELECT * FROM proxy';
		if($status !== null) {
			$query .= ' WHERE status='.$status;
		}
		$proxy_query = $this->database->getConnection()->query($query);
		return ($proxy_query->rowCount()) ? $proxy_query->fetchAll() : false;
	}

	/**
	 * Edit proxy
	 * @param int $proxy_id
	 * @param array $editValues
	 * @return void
	 */
	public function editProxy(int $proxy_id, array $editValues): void
	{
		if(count($editValues)) {
			$bind_values = [];
			$set_values = [];
			foreach($editValues as $column => $value) {
				$bind_values[':'.$column] = $value;
				$set_values[] = $column.'=:'.$column;
			}
			$connect = $this->database->getConnection()->prepare('UPDATE proxy SET '.implode(',', $set_values).' WHERE proxy_id='.$proxy_id);
			$connect->execute($bind_values);
		}
	}

	/**
	 * Random proxy
	 * @throws Exception
	 */
	public function randomProxy()
	{
		$proxy_list = $this->getProxyList(0);
		if(!$proxy_list) {
			throw new Exception(__LINE__.': '.Notice::W_NO_PROXY_WORKING);
		}
		$rand = rand(0, count($proxy_list) - 1);
		return $proxy_list[$rand];
	}

	/**
	 * Set random proxy
	 * @return mixed|false
	 * @throws Exception
	 */
	public function getRandomProxy(): mixed
	{
		$rand_proxy = $this->randomProxy();
		return (!$rand_proxy) ? false : $rand_proxy;
	}

	/**
	 * Server response handler if response is empty (proxy not working)
	 * @param int $proxy_id
	 * @return bool
	 */
	public function processResponse(int $proxy_id): bool
	{
		$this->editProxy($proxy_id, ['status' => '408', 'notice' => 'Request Timeout', 'uptime' => time(),]);
		return true;
	}


}