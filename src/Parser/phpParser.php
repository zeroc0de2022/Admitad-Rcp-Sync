<?php
declare(strict_types = 1);
/**
 * Description of Parser
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 * Date 12.04.2023
 */

namespace Cpsync\Parser;

use Cpsync\Database;
use Cpsync\Mapper\Proxy;
use Cpsync\Mapper\Const\Notice;
use Cpsync\Mapper\Tool;
use Cpsync\Parser\phpParser\ContentParser;
use Cpsync\Parser\phpParser\CurlRequest;
use Cpsync\Parser\phpParser\MultiCurl;
use Exception;
use PDOStatement;


/**
 * Class Parser
 */
class phpParser
{
	/**
	 * Tool for parsing
	 * @var string
	 */
	private string $toolName;

	/**
	 * Request options
	 * @var array $requestOpt
	 */
	private array $requestOpt;

	/**
	 * Last handle id
	 * @var int
	 */
	private int $lastHandleId;

	/**
	 * Params
	 * @var array
	 */
	public array $params = [];

	public CurlRequest $curlRequest;
	/**
	 * Tool
	 * @var object
	 */
	public object $tool;

	/**
	 * Database
	 * @var Database $database
	 */
	public Database $database;

	/**
	 * Proxy
	 * @var Proxy
	 */
	private Proxy $proxy;

	/**
	 * Content parser
	 * @var ContentParser
	 */
	private ContentParser $contentParser;

	/**
	 * @var MultiCurl
	 */
	private MultiCurl $multiCurl;

	/**
	 * Constructor
	 * @param Proxy $proxy
	 * @param Tool $tool
	 * @param Database $database
	 * @param ContentParser $contentParser
	 * @param \Cpsync\Parser\phpParser\CurlRequest $curlRequest
	 * @param MultiCurl $multiCurl
	 */
	public function __construct(Proxy $proxy, Tool $tool, Database $database, ContentParser $contentParser,  CurlRequest $curlRequest, MultiCurl $multiCurl)
	{
		$this->tool = $tool;
		$this->database = $database;
		$this->proxy = $proxy;
		$this->contentParser = $contentParser;
		$this->curlRequest = $curlRequest;
		$this->multiCurl = $multiCurl;
	}

	/**
	 * Parse product
	 * @param $tool_name
	 * @return void
	 * @throws Exception
	 */
	public function parseProduct($tool_name): void
	{
		// Get product to parse
		$connect = $this->productToParse($tool_name);
		while($row = $connect->fetch()) {
			$this->addHandlers($row);
		}
		// Threads execution
		$this->multiCurl->threadAll();
		// Server response handler
		$this->handleResponses();
	}


	/**
	 * Get product to parse
	 * @param string $tool_name
	 * @return PDOStatement
	 * @throws \Exception
	 */
	private function productToParse(string $tool_name): PDOStatement
	{
		$this->toolName = $tool_name;
		if(empty($this->tool->toolConf)) {
			$this->tool->getToolConf($this->toolName);
		}
		$sql = 'SELECT * FROM product WHERE tool_name=:tool_name AND pars_status=0 LIMIT :limit';
		$connect = $this->database->getConnection()->prepare($sql);
		$result = $connect->execute([':tool_name' => $this->toolName, ':limit' => $this->tool->toolConf['thread']]);
		if(!$result) {
			$this->tool->editToolInfo($this->toolName, ['product_notice' => Notice::W_NO_PRODUCT_TO_PARSE]);
			throw new Exception(__LINE__.': '.Notice::W_NO_PRODUCT_TO_PARSE);
		}
		return $connect;
	}

	/***
	 * Add product parsing info
	 * @param int $handle_id
	 * @return void
	 * @throws \Exception
	 */
	private function addParserInfo(int $handle_id): void
	{
		$tool_info = $this->tool->toolInfo[$this->toolName];
		$this->tool->toolInfo[$this->toolName]['csv_parsed_product_sum']++;
		$this->tool->editToolInfo($this->toolName, ['csv_parsed_product_sum' => $tool_info['csv_parsed_product_sum'], 'product_notice' => 'ok - ID: '.$handle_id.' - .'.date('Y-m-d H:i:s', time())]);
		if(!preg_match('#ok#i', $tool_info['product_notice'])) {
			$this->tool->editToolInfo($this->toolName, ['product_notice' => 'ok - '.date('Y-m-d H:i:s', time())]);
		}
	}

	/***
	 * Server response handler
	 * @return void
	 * @throws Exception
	 */
	private function handleResponses(): void
	{
		foreach($this->multiCurl->curlHandles as $handle_id => $curl_handle) {
			$html_code = $this->multiCurl->getHandleContent($curl_handle);

			if(strlen($html_code) < 100) {
				if($this->tool->toolConf['proxy_status'] === 1) {
					$this->emptyResponce($handle_id);
				}
				continue;
			}
			$content = $this->contentParser->parseData($handle_id, $html_code);
			if($content[$handle_id]['status']) {
				$bind_values = $content[$handle_id]['bind_values'];
				$this->addDataInDB($bind_values, $handle_id);
				// Add parsing info
				$this->addParserInfo($handle_id);
				print_r(__LINE__.'. ID '.$handle_id.' - '.Notice::N_PRODUCT_PARSED.'<br />');
				if($this->lastHandleId === $handle_id) {
					throw new Exception(__LINE__.': '.Notice::N_PRODUCT_PARSED);
				}
				continue;
			}
			$this->setStatus($handle_id, $content[$handle_id]['code']);
			$this->errorCount($handle_id, $html_code);
			unset($html_code);
			throw new Exception(__LINE__.'. ID '.$handle_id.' - '.Notice::E_PARSE_CONTENT);
		}
	}

	/**
	 * Server response handler if response is empty (proxy not working)
	 * @param int $handle_id
	 * @return void
	 * @throws \Exception
	 */
	private function emptyResponce(int $handle_id): void
	{
		if($this->tool->toolConf['proxy_status'] === 1) {
			$proxy_id = $this->requestOpt[$handle_id]['proxy']['proxy_id'];
			$process_response = $this->proxy->processResponse($proxy_id);
			if(!$process_response) {
				$this->setStatus($handle_id, 408);
			}
		}
	}

	/**
	 * Add collected data to database
	 * @param array $bind_values
	 * @param int $handle_id
	 * @throws \Exception
	 */
	private function addDataInDB(array $bind_values, int $handle_id): void
	{
		// If data is successfully collected, then add to the database
		$sql = 'INSERT IGNORE INTO product_data (product_id, images, description, attrs, reviews) VALUES (:product_id, :images, :description, :attrs, :reviews)';
		try {
			$connect = $this->database->getConnection()->prepare($sql);
			$connect->execute($bind_values);
			$this->setStatus($handle_id);
		} catch(Exception $exception) {
			throw new Exception(__LINE__.': '.Notice::E_ADD_DATA.' - '.$exception->getMessage());
		}

	}

	/**
	 * Add handlers to the queue
	 * @param array $row - product data
	 * @return void
	 * @throws Exception
	 */
	private function addHandlers(array $row): void
	{
		if(strlen($row['url']) > 10) {
			$this->lastHandleId = $row['product_id'];
			$this->contentParser->init($row);
			$handle_id = $row['product_id'];
			// Set request options
			$this->requestOpt[$handle_id] = ['url' => $row['url'], 'follow' => 1];
			// If proxy enabled
			$this->setRandomProxy($handle_id);
			// Random useragent
			$this->requestOpt[$handle_id]['ua'] = (new UserAgent())->randomUseragent();
			// Add request to thread
			$result = $this->curlRequest->pRequest($this->requestOpt[$handle_id]);
			// Add curl handler to multcurl
			$this->multiCurl->addCurlHandle($handle_id, $result['ch']);

		} else {
			$this->setStatus($row['product_id'], 400);
		}
	}

	/**
	 * Set random proxy for request
	 * @param $product_id - product id
	 * @throws Exception
	 */
	private function setRandomProxy(int $product_id): void
	{
		if($this->tool->toolConf['proxy_status']) {
			$rand_proxy = $this->proxy->getRandomProxy();
			if(!$rand_proxy) {
				$this->tool->editToolInfo($this->toolName, ['product_notice' => Notice::W_NO_PROXY_WORKING]);
				$this->tool->editToolConf($this->toolName, ['product_status' => 0]);
				throw new Exception(__LINE__.': '.Notice::W_NO_PROXY_WORKING);
			}
			$this->requestOpt[$product_id]['proxy'] = $rand_proxy;
		}
	}


	/***
	 * Count errors
	 * @param $handle_id
	 * @param $html_code - html code
	 * @return void
	 */
	private function errorCount($handle_id, $html_code): void
	{
		$error_file = __DIR__.'/errors/'.$handle_id.'_'.date('Ymd_His').'.html';
		file_put_contents($error_file, $html_code);

		$error_file = __DIR__.'/files/error_count.txt';
		if(!file_exists($error_file)) {
			file_put_contents($error_file, 0);
		}
		$error_count = file_get_contents($error_file);
		$error_count = ($error_count >= $this->tool->toolConf['error_count']) ? 0 : ++$error_count;
		file_put_contents($error_file, $error_count);
	}

	/***
	 * Set status to product
	 * @param int $product_id
	 * @param int $status
	 * @throws Exception
	 */
	private function setStatus(int $product_id, int $status = 200): void
	{
		$connect = $this->database->getConnection()->query('UPDATE product SET pars_status='.$status.' WHERE product_id='.$product_id);
		if(!$connect) {
			throw new Exception(__LINE__.': '.Notice::E_PRODUCT_UPDATE);
		}
	}


}
