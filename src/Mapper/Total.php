<?php
declare(strict_types = 1);
/***
 * Date 21.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Mapper;

use Cpsync\Database;
use Cpsync\Mapper\Const\Notice;
use Cpsync\Mapper\Trait\Common;
use PDOException;

/**
 * Class Total
 */
class Total
{
	use Common;


	/**
	 * @var Database $database
	 */
	private Database $database;

	/**
	 * @param \Cpsync\Database $database
	 */
	public function __construct(Database $database)
	{
		$this->database = $database;
	}

	/**
	 * Get total counts
	 * @return array
	 */
	public function getTotalCounts(): array
	{
		return [
			'tools' => $this->getToolsCount(),
			'product' => $this->getProductCount(),
			'proxy' => $this->getProxyCount(),
			'db' => $this->getStrSize($this->database->getDBSize()),
			'hdd' => $this->getHddSize(),];
	}

	/**
	 * Get proxy count in table
	 * @return int
	 */
	public function getProxyCount(): int
	{
		$query = 'SELECT count(proxy_id) as proxy_count FROM proxy';
		$connect = $this->database->getConnection()->query($query);
		if($connect) {
			return $connect->fetchColumn();
		}
		throw new PDOException(__LINE__.': '.Notice::E_REQUEST.' - '.$connect->errorInfo()[2]);

	}

	/***
	 * Get tool and importer count
	 * @return array
	 */
	public function getToolsCount(): array
	{
		$query = 'SELECT tool FROM tools';
		$request = $this->database->getConnection()->query($query);
		$result = ['tool' => 0, 'importer' => 0];
		while($row = $request->fetch()) {
			($row['tool'] == 'tool')
				? $result['tool']++
				: $result['importer']++;
		}
		return $result;
	}

	/**
	 * Get product count in table
	 * @return int
	 */
	public function getProductCount(): int
	{
		$query = 'SELECT count(product_id) as product_count FROM product';
		$connect = $this->database->getConnection()->query($query);
		if($connect) {
			return $connect->fetchColumn();
		}
		throw new PDOException(__LINE__.': '.Notice::E_REQUEST);
	}

}