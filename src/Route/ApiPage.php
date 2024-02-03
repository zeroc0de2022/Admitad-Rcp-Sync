<?php
declare(strict_types = 1);
/***
 * Date 09.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Mapper\Api;
use Cpsync\Mapper\Const\Notice;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Cpsync\Route\Interface\PageView;


/**
 * Class ApiPage
 * @package Cpsync\Route
 */
class ApiPage implements PageView
{
	/**
	 * @var Api $api
	 */
	private Api $api;

	/**
	 * @var object $session
	 */
	private object $session;

	/**
	 * @var object $tool
	 */
	private object $tool;

	/**
	 * @var array $params
	 */
	private array $params = [];

	/**
	 * ApiPage constructor.
	 * @param Api $api
	 */
	public function __construct(Api $api)
	{
		$this->api = $api;
		$this->session = $api->session;
		$this->tool = $api->tool;
	}

	/**
	 * Execution transfer depending on the request method
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @param array $args
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function handleRequest(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
	{
		if($request->getMethod() === 'POST') {
			$this->params = (array)$request->getParsedBody();
			try {
				$this->handlePost($request, $response);
			} catch(Exception $exception) {
				$this->session->setData('message', $exception->getMessage());
				$this->session->setData('form', $this->params);
				return $response->withHeader('Location', '/api')->withStatus(302);
			}
		}
		return $response;
	}

	/**
	 * Get request handler
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 ***/
	public function handleGet(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		// TODO: Implement handleGet() method.
		return $response;
	}

	/**
	 * POST request handler
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 * @throws Exception
	 ***/
	public function handlePost(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$allowed_cats = ['product', 'categories', 'adi', 'rmi', 'upi'];
		$this->params['action'] = (string)$this->params['action'] ?? false;
		$this->params['categoryId'] = (array)$this->params['categoryId'] ?? [];
		$this->params['tool_name'] = (string)$this->params['tool_name'] ?? false;
		$this->params['json'] = (string)$this->params['json'] ?? false;
		$this->params['rule'] = (string)$this->params['rule'] ?? false;

		// Checking for valid values
		if($this->params['action'] && count(array_intersect($allowed_cats, [$this->params['action']]))) {
			$this->switchAction();
			return $response;
		}
		throw new Exception(__LINE__.': '.Notice::W_INVALID_ACTION);
	}

	/***
	 * Prepare a request from the received data
	 * @param array $params
	 * @return array
	 */
	public function getProductBind(array $params): array
	{
		$categories = [];
		$result = ['limit' => '', 'bind_values' => []];
		foreach($params as $key => $value) {
			switch($key) {
				case 'categoryId':
					{
						$value = array_values(array_unique($value));
						foreach($value as $num => $category) {
							$categories[] = $key.' LIKE :category_'.$num;
							$result['bind_values'][':category_'.$num] = '%'.$category.'%';
						}
					}
					break;
				case 'limit':
					{
						$result['limit'] = ($value > 0) ? " $key $value" : '';
					}
					break;
			}
		}
		$result['category'] = implode(' OR ', $categories);
		return $result;
	}

	/**
	 * Processing a request depending on the action parameter
	 * @return void
	 * @throws Exception
	 */
	private function switchAction(): void
	{
		$tool_name = $this->params['tool_name'] ?? false;
		$values = ($this->params['json'] ? json_decode($this->params['json'], true) : false) ?? false;
		$rule = $this->params['rule'] ?? false;
		$cat_count = (count($this->params['categoryId']));

		switch($action = $this->params['action']) {
			case ($action == 'product' && $cat_count) :
				{
					$result = $this->getProductBind($this->params);
					$this->api->getProductData($result);
				}
				break;
			case 'categories' :
				$this->api->getCategoryData();
				break;
			case 'adi' :
				$this->tool->newTool($this->params);
				break;
			case ($action == 'rmi' && $tool_name && $rule !== false) :
				{
					$this->tool->removeTool($tool_name, $rule, 'importer');
				}
				break;
			case ($action == 'upi' && $tool_name && $values !== false) :
				$this->tool->editToolInfo($tool_name, $values);
				break;
			default :
			{
				throw new Exception(__LINE__.': '.Notice::W_INVALID_PARAM);
			}
		}
	}


}