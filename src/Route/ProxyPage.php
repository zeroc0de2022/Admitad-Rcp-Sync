<?php
declare(strict_types = 1);
/***
 * Date 02.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Mapper\Tool;
use Cpsync\Mapper\Proxy;
use Cpsync\Route\Interface\PageView;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Cpsync\Mapper\Trait\BlockUser;

/**
 * Class ProxyPage
 */
class ProxyPage implements PageView
{
	use BlockUser;

	/**
	 * Database $database
	 * @var object $database
	 */
	private object $database;

	/**
	 * @var Proxy $proxy
	 */
	private Proxy $proxy;

	/**
	 * @var mixed|null
	 */
	private mixed $userSession;

	/**
	 * Session $session
	 * @var object $session
	 */
	private object $session;

	/**
	 * Environment $view
	 * @var object $view
	 */
	private object $view;

	/**
	 * @var Tool $tool ;
	 */
	private Tool $tool;


	/**
	 * ProxyPage constructor.
	 * @param Proxy $proxy
	 * @throws Exception
	 */
	public function __construct(Proxy $proxy)
	{
		$this->proxy = $proxy;
		$this->database = $proxy->database;
		$this->session = $proxy->session;
		$this->view = $proxy->view;
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
		if(null == $this->userSession) {
			return $response->withHeader('Location', '/login')->withStatus(302);
		}
		if($request->getMethod() === 'POST') {
			$this->handlePost($request, $response);
			return $response->withHeader('Location', '/proxy')->withStatus(302);
		} elseif($request->getMethod() === 'GET') {
			$this->handleGet($request, $response);
			return $response;
		}
		return $response->withHeader('Location', '/proxy')->withStatus(302);
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
		$proxy_list = $this->proxy->getProxyList();
		$tools = $this->tool->getAllTools();
		try {
			$body = $this->view->render('proxy.twig', ['proxyList' => $proxy_list, 'tools' => $tools, 'UserAddintional' => true, 'user' => $this->userSession]);
		} catch(LoaderError|SyntaxError|RuntimeError $exception) {
			throw new Exception(__LINE__.': '.$exception->getMessage());
		}
		$response->getBody()->write($body);
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
		$params = (array)$request->getParsedBody();
		if(isset($params['proxyList'])) {
			try {
				$this->proxy->addProxyList($params['proxyList']);
			} catch(Exception $exception) {
				$this->session->setData('message', $exception->getMessage());
				$this->session->setData('form', $params);
			}
		} elseif($params['action'] === 'prodel') {
			$this->proxy->deleteProxy($params);
		}
		return $response;
	}
}