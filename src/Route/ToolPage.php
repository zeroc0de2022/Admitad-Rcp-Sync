<?php
declare(strict_types = 1);
/***
 * Date 02.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\CronTask;
use Cpsync\Mapper\Tool;
use Cpsync\Route\Interface\PageView;
use Cpsync\Session;
use Twig\Environment;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Cpsync\Mapper\Trait\BlockUser;
use Cpsync\Mapper\Trait\Validator;
use Cpsync\Mapper\Trait\Message;
use Cpsync\Mapper\Trait\Debug;

/**
 * Class ToolPage
 */
class ToolPage implements PageView
{
	use BlockUser;
	use Validator;
	use Message;
	use Debug;

	/**
	 * @var Tool $tool
	 */
	private Tool $tool;

	/**
	 * @var Environment $view
	 */
	private Environment $view;

	/**
	 * @var mixed|null $userSession
	 */
	private mixed $userSession;

	/**
	 * @var mixed|null $args
	 */
	private mixed $args;

	/**
	 * @var Session $session
	 */
	private Session $session;



	/**
	 * @var CronTask $cronTask
	 */
	private CronTask $cronTask;

	/**
	 * ToolPage constructor.
	 * @param Tool $tool
	 * @param Environment $view
	 * @param Session $session
	 * @param CronTask $cronTask
	 * @throws Exception
	 */
	public function __construct(Tool $tool, Environment $view, Session $session, CronTask $cronTask)
	{
		$this->cronTask = $cronTask;
		$this->view = $view;
		$this->tool = $tool;
		$this->session = $session;
		$this->userSession = $this->session->getData('user');
		$this->isBanned();
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
		if($this->userSession == null) {
			return $response->withHeader('Location', '/login')->withStatus(302);
		}
		$this->args = $args;
		return ($request->getMethod() === 'POST')
			? $this->handlePost($request, $response)
			: $this->handleGet($request, $response);
	}

	/**
	 * Get request handler
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 * @throws Exception
	 ***/
	public function handleGet(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$tool = $this->tool->getByToolName((string)$this->args['tool_name']);

		if(empty($tool)) {
			return $response->withHeader('Location', '/tools')->withStatus(302);
		}
		try {
			$cronTask = $this->cronTask->cron->getCronTask($tool['tool_name']);
			$body = $this->view->render('tool.twig', [
				'tool' => $tool,
				'tools' => $this->tool->getAllTools(),
				'user' => $this->userSession,
				'cronTask' => $cronTask]);
			$response->getBody()->write($body);
		} catch(LoaderError|SyntaxError|RuntimeError $exception) {
			throw new Exception(__LINE__.': '.$exception->getMessage());
		}
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
		$action_result = false;
		$params = (array)$request->getParsedBody();
		if(isset($params['action'])) {
			$action = $params['action'];
			$actions = ['editTool', 'removeTool', 'newTool', 'editToolConf', 'addCronTask', 'removeCronTask'];
			if(in_array($action, $actions, true)) {
				$action_result = $this->$action($params);
			}
		}
		$location = $request->getUri()->getPath();
		return ($action_result)
			? $response
			: $response->withHeader('Location', $location)->withStatus(302);
	}

	/**
	 * Add cron task
	 * @param array $params
	 * @return bool
	 * @throws \Exception
	 */
	public function addCronTask(array $params): bool
	{
		$this->cronTask->prepareParamsToCron($params);
		return false;
	}

	/**
	 * Remove cron task
	 * @throws \Exception
	 */
	public function removeCronTask(array $params): bool
	{
		$this->cronTask->cron->removeCronTask($params['id']);
		return true;
	}

	/**
	 * New Tool
	 * @param array $params
	 * @return bool
	 * @throws Exception
	 */
	public function newTool(array $params): bool
	{
		return $this->tool->newTool($params);
	}

	/**
	 * Prepare params to edit tool
	 * @param array $params
	 * @return bool
	 */
	public function editTool(array $params): bool
	{
		return $this->tool->editTool($params['tool_name'], $params['name'], $params['value']);
	}

	/**
	 * Edit Tool config
	 * @param array $params
	 * @return bool
	 */
	public function editToolConf(array $params): bool
	{
		$update = [$params['name'] => $params['value']];
		return $this->tool->editToolConf($params['tool_name'], $update);
	}

	/**
	 * Remove Tool
	 * @param array $params
	 * @return bool
	 */
	public function removeTool(array $params): bool
	{
		return $this->tool->removeTool($params['id'], $params['rule']);
	}



	/**
	 * Execution transfer depending on the request method
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 * @throws \Exception
	 */
	public function requestHandle(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		if($this->userSession == null) {
			$response->withHeader('Location', '/login')->withStatus(302);
		}
		if($request->getMethod() === 'GET') {
			$this->requestGetHandle($response);
		}
		return $response;
	}

	/**
	 * Get request handler
	 * @param ResponseInterface $response
	 * @return void
	 * @throws \Exception *
	 */
	public function requestGetHandle(ResponseInterface $response): void
	{
		try {
			$body = $this->view->render('tools.twig', ['tools' => $this->tool->getAllTools(), 'user' => $this->userSession]);
		} catch(LoaderError|SyntaxError|RuntimeError $exception) {
			throw new Exception(__LINE__.': '.$exception->getMessage());
		}
		$response->getBody()->write($body);
	}
}