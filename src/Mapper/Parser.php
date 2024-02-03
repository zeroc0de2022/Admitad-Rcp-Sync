<?php
declare(strict_types = 1);
/***
 * Date 23.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Mapper;

use Cpsync\Mapper\Const\Notice;
use Cpsync\Parser\csvParser;
use Cpsync\Parser\phpParser;
use Exception;

/**
 * Class Parser
 */
class Parser
{
	/**
	 * @var csvParser
	 */
	private csvParser $csvParser;

	/**
	 * @var Tool
	 */
	public Tool $tool;

	/**
	 * @var array $toolData
	 */
	private array $toolData;

	/**
	 * @var array $params
	 */
	private array $params;

	/**
	 * @var phpParser $parser
	 */
	private phpParser $parser;

	/**
	 * Parser constructor.
	 * @param Tool $tool
	 * @param csvParser $csvParser
	 * @param phpParser $parser
	 */
	public function __construct(Tool $tool, csvParser $csvParser, phpParser $parser)
	{
		$this->tool = $tool;
		$this->csvParser = $csvParser;
		$this->parser = $parser;
	}

	/**
	 * Init parser
	 * @param array $params
	 * @throws Exception
	 */
	public function init(array $params): void
	{
		// Preparing Parser primary config
		$this->params = $params;
		$this->toolData = $this->tool->prepareToolConf($this->params['tool_name']);
		$this->selectParsingRoute();
	}

	/**
	 * Select parser to run parsing (csv, products)
	 * @throws Exception
	 */
	public function selectParsingRoute(): void
	{
		if($this->toolData) {
			switch($this->params['action']) {
				// Parsing csv file
				case 'csv':
					$this->actionCsv();
					break;
				// Parsing products
				case 'product':
					$this->actionProduct();
					break;
				default:
					throw new Exception(__LINE__.': '.Notice::W_NO_PARSER);
			}
		}
	}

	/**
	 * Check if csv parsing is enabled
	 * @param string $val - [csv|product]_status
	 * @return bool
	 */
	private function isParsingEnabled(string $val): bool
	{
		return isset($this->tool->toolConf[$val]) && $this->tool->toolConf[$val] === 1;
	}

	/**
	 * Check if csv file is valid
	 * @param array $urlHeaders - headers from url
	 * @return bool
	 */
	private function isValidCsvFile(array $urlHeaders): bool
	{
		if(!isset($urlHeaders[0]) || !str_contains($urlHeaders[0], '200')) {
			return false;
		}
		$headers = $this->parsHeaders($urlHeaders);
		return !empty($headers['Content-Type']) && $headers['Content-Type'] === 'text/csv';
	}

	/**
	 * Start csv parser
	 * @return void
	 * @throws Exception
	 */
	private function startCsvParser(): void
	{
		$this->params['stop'] = $this->tool->toolConf['limit_parsed_line'];
		$this->params['remote_link'] = $this->toolData['remote_link'];
		$this->params['tool_name'] = $this->toolData['tool_name'];

		$this->csvParser->prepareParams($this->params);
		$this->csvParser->start('get');
	}

	/**
	 * Parsing csv file
	 * @throws Exception
	 * @return void
	 */
	private function actionCsv(): void
	{
		$tool_name = $this->toolData['tool_name'];
		# If csv parsing is enabled in the panel
		if($this->isParsingEnabled('csv_status')) {
			$url_headers = get_headers($this->toolData['remote_link']);
			if($this->isValidCsvFile($url_headers)) {
				$this->startCsvParser();
				return;
			}
			$this->tool->editToolInfo($tool_name, ['csv_notice' => Notice::W_INVALID_FILE]);
			$this->tool->editToolConf($tool_name, ['csv_status' => 0]);
		}
		if($this->isManualRun()) {
			throw new Exception(__LINE__.': '.Notice::N_PARSER_DISABLED);
		}
	}

	/**
	 * Check if manual run is enabled
	 * @return bool
	 */
	public function isManualRun(): bool
	{
		return (isset($this->params['run']) && $this->params['run'] === 'manual');
	}

	/**
	 * Parsing products
	 * @return void
	 * @throws Exception
	 */
	private function actionProduct(): void
	{

		$tool_name = $this->toolData['tool_name'];
		// If product parsing is enabled in the panel
		if($this->isParsingEnabled('product_status')) {
			// Preparing Parser primary config
			if(empty($this->tool->toolConf)) {
				$this->tool->getToolConf($tool_name);
			}
			// Products parser
			$this->parser->parseProduct($tool_name);
			if($this->isManualRun()) {
				print_r('<meta http-equiv="refresh" content="'.$this->tool->toolConf['up_sec'].'; url=/'.$this->getUri().'"/>');
			}
		} elseif($this->isManualRun()) {
			throw new Exception(__LINE__.': '.Notice::N_PARSER_DISABLED);
		}
	}

	/**
	 * Function for getting headers
	 * @param array $headers - headers from url
	 * @return array
	 */
	public function parsHeaders(array $headers): array
	{
		$arr_assoc = [];
		foreach($headers as $halue) {
			$divider = (str_contains($halue, ':')) ? ':' : ' ';
			[$key, $value] = explode($divider, $halue, 2);
			$arr_assoc[$key] = isset($arr_assoc[$key]) ? trim($arr_assoc[$key].'; '.$value) : trim($value);
		}
		return $arr_assoc;
	}

	/**
	 * Returns URI
	 * @return string
	 */
	public function getUri(): string
	{
		$uri = trim($_SERVER['REQUEST_URI'], '/');
		return (!empty($uri)) ? trim($uri, '/') : $uri;
	}


}