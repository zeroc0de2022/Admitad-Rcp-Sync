<?php
declare(strict_types = 1);
/**
 * Description of csvParser
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 * Date 17.04.2023
 */

namespace Cpsync\Parser;

use Cpsync\Database;
use Cpsync\Mapper\Const\Constants;
use Cpsync\Mapper\Tool;
use Cpsync\Mapper\Trait\Common;
use Cpsync\Mapper\Trait\Validator;
use Exception;

/**
 * Class csvParser
 * @package Cpsync\Parser
 * Parsing csv file and updating data in DB (parsing)
 */
class csvParser
{
	use Validator;
	use Common;

	/**
	 * @var Tool $tool
	 */
	private Tool $tool;

	/**
	 * @var Database $database
	 */
	private Database $database;

	/**
	 * @var string $download
	 */
	private string $download;

	/**
	 * @var array $params
	 */
	private array $params = [];

	/**
	 * @var int $productSum
	 */
	private int $productSum;

	/**
	 * @var int $fileSize
	 */
	private int $fileSize = 0;

	/**
	 * @param array $params
	 */
	public function prepareParams(array $params): void
	{
		$this->download = dirname(__DIR__, 2).'/files/download';
		$this->params = $params;
	}

	/**
	 * @param Tool $tool
	 * @param Database $database
	 */
	public function __construct(Tool $tool, Database $database)
	{
		$this->tool = $tool;
		$this->database = $database;
	}

	/**
	 * Get csv file from remote server and update data in DB (parsing)
	 * @throws Exception
	 * @return void
	 */
	public function getCsv(): void
	{
		// If there is no data file, create it
		if(!file_exists($this->download)) {
			file_put_contents($this->download, '');
		}
		// Counting the time since the last data update
		$diff = $this->diffBetween2dates(time(), fileatime($this->download));
		// If more than 23 hours have passed, restart
		if($diff['hour'] > -1) {
			unlink($this->download);
			file_put_contents($this->download, '');
			$this->parseCsv();
		} else {
			echo 'only '.$diff['message'].' have passed\n';
		}
	}

	/**
	 * Start parsing csv
	 * @param $func
	 * @return bool
	 * @throws \Exception
	 */
	public function start($func): bool
	{
		if($func === 'parse') {
			$this->parseCsv();
		} elseif($func === 'get') {
			$this->getCsv();
		}
		return false;
	}

	/**
	 * Parsing csv file
	 * @return void
	 * @throws Exception
	 */
	public function parseCsv(): void
	{
		$tool_name = $this->params['tool_name'];
		$connect = $this->database->getConnection()->prepare('UPDATE product SET check_new=0,available=0 WHERE tool_name=:tool_name');
		$connect->execute(['tool_name' => $tool_name]);
		$this->handleCsv($tool_name);
	}

	/**
	 * Update info about upload
	 * @param $tool_name
	 * @param $updated_product_sum
	 * @throws Exception
	 */
	private function updProdInfo($tool_name, $updated_product_sum): void
	{
		// get the number of new products
		$query = $this->database->getConnection()->query('SELECT count(product_id) as newp FROM product WHERE check_new=1');
		$new = $query->fetch();
		// All updated rows - new products = updated products
		$tool_info = $this->tool->getStatToolInfo();
		$new_info = ['csv_product_sum' => $this->productSum, // Size of the last upload
			'csv_upload_size' => $this->getStrSize($this->fileSize), // Date / time of the last upload
			'csv_upload_time' => time(), // Sum of products in the last upload
			'csv_new_product_sum' => $new['newp'], 'csv_updated_product_sum' => ($updated_product_sum - $new['newp']), 'all_product_sum' => $tool_info[$tool_name]['csv_product_sum'] + $new['newp'], 'csv_notice' => 'ok - '.date('Y-m-d H:i:s', time())];
		if(str_contains($tool_info[$tool_name]['csv_notice'], 'ok')) {
			$new_info['csv_notice'] = 'ok - '.date('Y-m-d H:i:s', time());
		}
		$this->tool->editToolInfo($tool_name, $new_info);

		// Output results
		if(isset($this->params['run']) && $this->params['run'] == 'manual') {
			echo nl2br(" - Total entries: {$new_info['csv_product_sum']}\n
                                    - New: {$new_info['csv_new_product_sum']}\n 
                                     - Updated: {$new_info['csv_updated_product_sum']}\n
                                      - Size: {$new_info['csv_upload_size']}\n");
		}
		// $new = get the number of new products, add to config
		// $updated = total number of all changed items (added + changed)
		// $updated - $new = products with updated price
	}

	/**
	 * Get bind data for query
	 * @param array $insert_cols
	 * @param bool|array $data_o
	 * @return array
	 */
	private function getBindData(array $insert_cols, bool|array $data_o): array
	{
		$bind_data = [];
		foreach($data_o as $key => $value) {
			$this->fileSize += strlen($value);
			$value = (in_array($insert_cols[$key], ['available', 'modified_time', 'id'], true) &&
				is_numeric($value)) ? (int)$value : trim($value);

			if($insert_cols[$key] == 'id') {
				$bind_data[':product_id'] = $value;
				continue;
			}
			if(!in_array($insert_cols[$key], Constants::COLUMNS, true)) {
				continue;
			}
			if($insert_cols[$key] == 'picture') {
				$value = str_replace('/preview', '', $value);
			}
			if($insert_cols[$key] == 'available') {
				$value = ($value == 'true') ? 1 : 0;
			}
			// Prepare update parameters if product already added to DB
			if(in_array($insert_cols[$key], ['available', 'modified_time', 'price'], true)) {
				$bind_data[':'.$insert_cols[$key].'_update'] = $value;
			}
			// Prepare new product parameters if product not in DB
			if($insert_cols[$key] == 'url') {
				$url = $this->extractContent('ulp=', '.html', urldecode(trim($value))).'.html';
				$bind_data[':url'] = $url;
				$tool_name = str_replace('www.', '', parse_url($url)['host']);
				$bind_data[':tool_name'] = $tool_name;
				$bind_data[':admitad'] = $value;
				continue;
			}
			$bind_data[':'.$insert_cols[$key]] = $value;
		}
		return $bind_data;
	}

	/**
	 * Csv file handler and update data in DB
	 * @param mixed $tool_name
	 * @return void
	 * @throws Exception
	 */
	private function handleCsv(mixed $tool_name): void
	{

		if(($handle_o = fopen($this->params['remote_link'], 'r')) !== FALSE) {
			// read first line and parse fields
			$columns_o = fgetcsv($handle_o, 1000, ';');
			$insert_cols = [];
			foreach($columns_o as $line) {
				$insert_cols[] = addslashes(trim($line));
			}
			$this->productSum = $all_upd = 0;
			while(($data_o = fgetcsv($handle_o, 1000, ';')) !== FALSE) {
				$this->productSum++;
				// Prepare data for query
				$bind_data = $this->getBindData($insert_cols, $data_o);
				// Insert/Update data in DB
				$this->csvInDb($bind_data, $all_upd);
				if($this->params['stop'] <= $this->productSum && $this->params['stop'] != 0) {
					break;
				}
			}
			$this->updProdInfo($tool_name, $all_upd);
		}
		fclose($handle_o);
	}

	/**
	 * @throws \Exception
	 */
	private function csvInDb($bind_data, &$all_upd): void
	{
		# Query template
		$query = $this->database->getConnection()->prepare('INSERT IGNORE INTO product SET available = :available, categoryId = :categoryId, currencyId = :currencyId, product_id = :product_id, model = :model, modified_time = :modified_time, name = :name, picture = :picture, price = :price, typePrefix = :typePrefix, url = :url, admitad = :admitad, tool_name = :tool_name, vendor = :vendor ON DUPLICATE KEY UPDATE available = :available_update, modified_time = :modified_time_update, price = :price_update');
		try {
			$query->execute($bind_data);
		} catch(Exception $exception) {
			throw new Exception(__LINE__.': '.__METHOD__.' -> '.$exception->getMessage());
		}
		if($query->rowCount()) {
			$all_upd++;
		}

	}


}
