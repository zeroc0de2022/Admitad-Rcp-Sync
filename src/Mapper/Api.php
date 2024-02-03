<?php
declare(strict_types = 1);
/**
 * Description of Api
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 * Date 07.04.2023
 */

namespace Cpsync\Mapper;

use Cpsync\Database;
use Cpsync\Mapper\Const\Constants;
use Cpsync\Mapper\Const\Notice;
use Cpsync\Mapper\Trait\Message;
use Cpsync\Session;
use Exception;

/**
 * Class Api
 * for interaction with rcp-sync-plugin
 */
class Api
{
	use Message;

	/**
	 * @var string $file
	 */
	private string $file;

	/**
	 * @var Database $database
	 */
	public Database $database;

	/**
	 * @var Session $session
	 */
	public Session $session;

	/**
	 * @var Tool $tool
	 */
	public Tool $tool;

	/**
	 * Api constructor.
	 * @param Database $database
	 * @param Session $session
	 * @param Tool $tool
	 */
	public function __construct(Database $database, Session $session, Tool $tool)
	{
		$this->database = $database;
		$this->session = $session;
		$this->tool = $tool;
		$this->createCsvFile();
	}

	/**
	 * Api destructor.
	 */
	public function __destruct()
	{
		unlink($this->file);
	}

	/**
	 * Get category data
	 * @return void
	 * @throws Exception
	 */
	public function getCategoryData(): void
	{
		$query = 'SELECT DISTINCT categoryId FROM product';
		$connect = $this->database->getConnection()->query($query);
		$categories = $connect->fetchAll();
		if($categories !== false && count($categories)) {
			$this->genCategoryFile($categories);
		} else {
			$this->setMessage(Notice::W_NO_CATEGORY);
			throw new Exception($this->getMessageJson());
		}
	}

	/**
	 * Save category data to file and return file
	 * @return void
	 * @param array $categories - category data
	 */
	private function genCategoryFile(array $categories): void
	{
		$handle = fopen($this->file, 'a');
		foreach($categories as $category) {
			$csv_data = explode('/', $category['categoryId']);
			fputcsv($handle, $csv_data, ';');
		}
		fclose($handle);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=out.csv');
		readfile($this->file);
	}

	/**
	 * Get product data
	 * @param array $result
	 * @return void
	 * @throws Exception
	 */
	public function getProductData(array $result): void
	{
		$connect = $this->database->getConnection()->prepare("SELECT * FROM product,product_data WHERE product_data.product_id = product.product_id AND product.pars_status=200 AND ({$result['category']}) ".$result['limit']);
		$connect->execute($result['bind_values']);
		$products = $connect->fetchAll();
		if($products !== false && count($products)) {
			$this->genProductFile($products);
		} else {
			$this->setMessage(Notice::W_NO_PRODUCT);
			throw new Exception($this->getMessageJson());
		}
	}

	/**
	 * Generate product data to file and return file to client
	 * @param array $products
	 * @return void
	 */
	private function genProductFile(array $products): void
	{
		$stream = fopen($this->file, 'a');
		// set table names
		fputcsv($stream, Constants::FIELDS, ';');
		# set table values
		foreach($products as $product) {
			$fields = $this->getProductArray($product);
			fputcsv($stream, $fields, ';');
		}
		fclose($stream);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename=out.csv');
		readfile($this->file);
	}

	/**
	 * Link products to tables and return data
	 * @param array $product
	 * @return array
	 */
	private function getProductArray(array $product): array
	{
		$product_data = [];
		foreach(Constants::FIELDS as $field) {
			$product_data[] = $product[$field];
		}
		return $product_data;
	}

	/**
	 * Create new csv file
	 * @return void
	 */
	private function createCsvFile(): void
	{
		$this->file = dirname(__DIR__).'/files/out.csv';
		file_put_contents($this->file, '');
	}

}