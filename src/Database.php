<?php
declare(strict_types = 1);
/***
 * Date 01.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync;

use PDO;
use PDOException;
use Exception;

/**
 * Class Database
 */
class Database
{
	/**
	 * @var PDO $connection
	 */
	private PDO $connection;

	/**
	 * Database constructor.
	 * @param PDO $connection
	 */
	public function __construct(PDO $connection)
	{
		if(!isset($this->connection)) {
			$this->connection = $connection;
		}
	}

	/**
	 * Get the PDO connection
	 * @return PDO
	 */
	public function getConnection(): PDO
	{
		return $this->connection;
	}

	/**
	 * Prepare query and execution
	 * @param string $query The query string like 'SELECT * FROM table WHERE id = :id'
	 * @param array $binds The binds array like [':id' => 1]
	 * @param string $returnType The return type like 'fetch', 'fetchAll', 'rowCount', 'lastInsertId'
	 * @return mixed
	 * @throws \Exception
	 */
	public function preparedQuery(string $query, array $binds = [], string $returnType = 'null'): mixed
	{
		$binds = count($binds)
			? $binds
			: [];
		$connect = $this->connection->prepare($query);
		try {
			$connect->execute($binds);
		} catch(Exception $exception) {
			throw new Exception(__LINE__.': '.$exception->getMessage());
		}
		return (method_exists($connect, $returnType))
			? $connect->$returnType()
			: $connect;
	}


	/**
	 * Returns the current database name
	 * @return string
	 */
	public function getDatabaseName(): string
	{
		try {
			$result = $this->connection->query('SELECT DATABASE()');
			$db_name = $result->fetchColumn();
		} catch(PDOException $exception) {
			throw new PDOException(__LINE__.': '.$exception->getMessage());
		}
		return $db_name;
	}

	/**
	 * Returns the size of the database
	 * @return int
	 */
	public function getDBSize(): int
	{
		$db_name = $this->getDatabaseName();
		$request = $this->connection->query("SELECT SUM(data_length + index_length) AS 'size' FROM information_schema.TABLES WHERE table_schema = '$db_name' GROUP BY table_schema");

		return (int)$request->fetchColumn();
	}

}