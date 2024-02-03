<?php
declare(strict_types = 1);
/***
 * Date 20.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Mapper;

use Cpsync\Database;
use Cpsync\Mapper\Const\Constants;
use Cpsync\Mapper\Const\Notice;
use Cpsync\Mapper\Trait\Validator;
use Exception;
use PDO;
use Cpsync\Mapper\Trait\Message;

/**
 * Class Tool
 */
class Tool
{
	use Validator;
	use Message;

	/**
	 * tool info
	 * @var array
	 */
	public array $toolInfo;

	/**
	 * Tool config
	 * @var array $toolConf
	 */
	public array $toolConf;

	private PDO $connect;

	/**
	 * Tool constructor
	 */
	public function __construct(Database $database)
	{
		$this->connect = $database->getConnection();
		if(empty($this->toolInfo)) {
			$this->getToolInfo();
		}
	}

	/**
	 * Set static tool info
	 * @param string $key
	 * @param $value
	 */
	public function setStatToolInfo(string $key, $value): void
	{
		$this->toolInfo[$key] = $value;
	}

	/**
	 * Get static tool info
	 * @param string|null $key
	 * @return array
	 */
	public function getStatToolInfo(string $key = null): array
	{
		return $this->$toolInfo[$key] ?? $this->toolInfo;
	}

	/**
	 * Get tool info
	 * @param string $tool_name tool name
	 * @return array
	 */
	public function getByToolName(string $tool_name): array
	{
		$query = 'SELECT * FROM tools, tools_info, tools_config WHERE tools.tool_name = :tool_name AND tools.tool_name = tools_config.tool_name AND tools.tool_name = tools_info.tool_name';
		$request = $this->connect->prepare($query);
		$request->execute([
			':tool_name' => $tool_name
		]);
		$result = $request->fetchAll();
		if(empty($result)) {
			return [];
		}
		array_walk($result, function(&$item) {
			$item['info'] = json_decode($item['info'], true);
			$item['config'] = json_decode($item['config'], true);
		});
		return array_shift($result);
	}

	/***
	 * Get list of all tools
	 * @return array
	 */
	public function getAllTools(): array
	{
		$query = 'SELECT * FROM tools, tools_info WHERE tools.tool_name = tools_info.tool_name ';
		$request = $this->connect->prepare($query);
		$request->execute();
		$tool_list = [];
		while($row = $request->fetch()) {
			$row['info'] = json_decode($row['info'], true);
			$tool = $row['tool'];
			$tool_list[$tool][] = $row;
		}
		return $tool_list;
	}

	/**
	 * New tool from modal window
	 * @param array $params
	 * @return bool
	 * @throws Exception
	 */
	public function newTool(array $params): bool
	{
		extract($params);
		$is_true = (!isset($this->toolInfo[$tool_name]) && in_array($tool, ['tool', 'importer'],true));
		if(!$is_true) {
			$this->setMessage(Notice::N_TOOL_ADDED_ALREADY);
		}
		else {
			$query = 'INSERT INTO tools (reg_time, tool_name, remote_link, tool) VALUES (:reg_time,:tool_name,:remote_link,:tool)';
			$stmt = $this->connect->prepare($query);
			try {
				$result = $stmt->execute([
					':reg_time' => time(),
					':tool_name' => $tool_name,
					':remote_link' => $remote_link,
					':tool' => $tool
				]);
				if($result) {
					$this->setToolInfo($tool_name, $tool);
					if($tool == 'tool') {
						$this->setToolConf($tool_name);
					}
					$this->setMessage(Notice::N_TOOL_ADDED, true);
				}
				else{
					$this->setMessage(Notice::E_ADD_NEW_TOOL);
				}

			} catch(Exception $exception) {
				$this->setMessage(Notice::E_ADD_NEW_TOOL.' - '.$exception->getMessage());
			}
		}
		print_r($this->getMessageJson());
		return true;
	}

	/**
	 * Set tool config by default
	 * @param string $tool_name
	 * @throws Exception
	 */
	private function setToolConf(string $tool_name): void
	{
		$default_config = json_encode(Constants::TOOLS_CONFIG);
		$query = 'INSERT INTO tools_config (tool_name, config) VALUES (:tool_name, :config)';
		$stmt = $this->connect->prepare($query);
		try {
			$stmt->execute([
				':tool_name' => $tool_name,
				':config' => $default_config
			]);
		} catch(Exception $exception) {
			throw new Exception(__LINE__.': '.Notice::E_ADD_CONFIG.': '.$exception->getMessage());
		}
	}

	/**
	 * Set tool info
	 * @param string $tool_name
	 * @param string $tool
	 * @throws Exception
	 */
	public function setToolInfo(string $tool_name, string $tool = 'tool'): void
	{
		if(!in_array($tool, ['tool', 'importer'], true)) {
			throw new Exception(__LINE__.': '.Notice::W_INVALID_PARAMS);
		}
		// Default Info values
		$data = $tool == 'tool' ? Constants::TOOL_DEFAULT_INFO : Constants::IMPORTER_DEFAULT_INFO;
		$default_info = json_encode($data);
		$query = 'INSERT INTO tools_info (tool_name, info) VALUES (:tool_name, :info)';
		$stmt = $this->connect->prepare($query);
		try {
			$stmt->execute([
				':tool_name' => $tool_name,
				':info' => $default_info
			]);
		} catch(Exception $exception) {
			throw new Exception(__LINE__.': '.Notice::E_CONFIG_INFO.': '.$exception->getMessage());
		}
	}

	/**
	 * Get tool data
	 * @param string $column
	 * @param string $value
	 * @return array|false
	 * @throws \Exception
	 */
	public function getTool(string $column, string $value): bool|array
	{
		if(!in_array($column, Constants::TOOLS_COLUMNS, true)) {
			return false;
		}
		$query = 'SELECT * FROM tools WHERE '.$column.'=:value';
		$stmt = $this->connect->prepare($query);
		$stmt->execute([
			':value' => $value
		]);
		return $stmt->fetch();
	}

	/***
	 * Update tool data
	 * @param string $tool_name
	 * @param string $column
	 * @param $value
	 * @return true
	 */
	public function editTool(string $tool_name, string $column, $value): bool
	{
		if(in_array($column, Constants::TOOLS_COLUMNS, true) && isset($this->toolInfo[$tool_name])) {
			$query = 'UPDATE tools SET '.$column.'=:value WHERE tool_name=:tool_name';
			$stmt = $this->connect->prepare($query);
			$stmt->execute([
				':value' => $value,
				':tool_name' => $tool_name
			]);
			return true;
		}
		return false;
	}

	/**
	 * Remove Tool
	 * @param string $tool_name
	 * @param string $rule
	 * @param string $tool
	 * @return bool
	 */
	public function removeTool(string $tool_name, string $rule, string $tool = 'tool'): bool
	{
		if(!isset($this->toolInfo[$tool_name])) {
			$this->setMessage(Notice::W_TOOLNAME_REQUIRED);
		}
		else {
			$query = 'DELETE FROM tools WHERE tool_name=:tool_name AND tool=:tool';
			$stmt = $this->connect->prepare($query);
			$stmt->execute([
				':tool' => $tool,
				':tool_name' =>  $tool_name
			]);
			if($rule == 'complete') {
				$query =  'DELETE FROM tools_config WHERE tool_name=:tool_name';
				$stmt = $this->connect->prepare($query);
				$stmt->execute([
					':tool_name' => $tool_name
				]);
				$this->connect->query('OPTIMIZE TABLE product');
			}
			$this->setMessage(Notice::N_TOOL_DELETED, true);
		}
		print_r($this->getMessageJson());
		return true;
	}

	/***
	 * Get Tool configuration
	 * @param string $tool_name
	 * @return void
	 */
	public function getToolConf(string $tool_name): void
	{
		$query = 'SELECT config FROM tools_config WHERE tool_name=:tool_name LIMIT 1';
		$stmt = $this->connect->prepare($query);
		$stmt->execute([
			':tool_name' => $tool_name
		]);
		$row = $stmt->fetch();
		if($row) {
			$this->toolConf = json_decode($row['config'], true);
		}
	}

	/**
	 * Update tool config
	 * @param string $tool_name
	 * @param array $values array of values for update
	 * @return bool result of update
	 */
	public function editToolConf(string $tool_name, array $values = []): bool
	{
		if(is_array($values) && count($values)) {
			$query = "UPDATE tools_config SET config = JSON_SET(config,CONCAT('$.', :name),:value) WHERE tool_name=:tool_name";
			$stmt = $this->connect->prepare($query);
			$stmt->bindParam(':tool_name', $tool_name);
			foreach($values as $key => $value) {
				$value = (is_numeric($value)) ? (int)$value : $value;
				$stmt->bindParam(':value', $value, (is_numeric($value)) ? PDO::PARAM_INT : PDO::PARAM_STR);
				$stmt->bindParam(':name', $key);
				$stmt->execute();
			}
			return true;
		}
		return false;
	}

	/**
	 * Get tool info
	 * @return void
	 */
	public function getToolInfo(): void
	{
		$stmt = $this->connect->query('SELECT * FROM tools_info');
		while($row = $stmt->fetch()) {
			$this->setStatToolInfo($row['tool_name'], json_decode($row['info'], true));
		}
	}

	/**
	 * Update tool info
	 * @param string $tool_name tool name
	 * @param array $values array of values for update
	 * @throws Exception error update info
	 */
	public function editToolInfo(string $tool_name, array $values = []): void
	{
		if(count($values)) {
			$query = "UPDATE tools_info SET info = JSON_SET(info, CONCAT('$.', :name), :value) WHERE tool_name=:tool_name";
			$stmt = $this->connect->prepare($query);
			foreach($values as $key => $value) {
				$value = (is_numeric($value)) ? (int)$value : $value;
				try {
					$stmt->execute([
						':tool_name' => $tool_name,
						':value' => $value,
						':name' => $key
					]);
				} catch(Exception $exception) {
					throw new Exception(__LINE__.': '.Notice::E_UPDATE_INFO.': '.$exception->getMessage());
				}
			}
		}
	}

	/**
	 * Prepare Tool params for run parsing products/file csv
	 * @param string $tool_name
	 * @return array|bool
	 * @throws \Exception
	 */
	public function prepareToolConf(string $tool_name): array|bool
	{
		$tool_data = $this->getTool('tool_name', $tool_name);
		if(isset($tool_data['tool'])) {
			$this->getToolConf($tool_name);
			return $tool_data;
		}
		return false;
	}

}