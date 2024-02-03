<?php
declare(strict_types = 1);
/***
 * Date 02.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Database;
use Cpsync\Mapper\Tool;
use Cpsync\Mapper\Total;
use Cpsync\Route\Interface\PageView;
use Cpsync\Session;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Cpsync\Mapper\Trait\BlockUser;

/**
 *
 */
class MainPage implements PageView
{
	use BlockUser;

	/**
	 * Tool $tool
	 */
	private Tool $tool;

	/**
	 * @var Session $session
	 */
	private Session $session;

	/**
	 * @var Environment $view
	 */
	private Environment $view;

	/**
	 * @var Database $database
	 */
	private Database $database;

	/**
	 * @var Total $total
	 */
	private Total $total;


	public mixed $userSession;

	/**
	 * MainPage constructor.
	 * @param Session $session
	 * @param Environment $view
	 * @param Database $database
	 * @param \Cpsync\Mapper\Tool $tool
	 * @param \Cpsync\Mapper\Total $total
	 * @throws \Exception
	 */
	public function __construct(Session $session, Environment $view, Database $database, Tool $tool, Total $total)
	{
		$this->database = $database;
		$this->session = $session;
		$this->tool = $tool;
		$this->userSession = $this->session->getData('user');
		$this->view = $view;
		$this->total = $total;
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
		if($this->session->getData('user') === null) {
			return $response->withHeader('Location', '/login')->withStatus(302);
		}
		return match ($request->getMethod()) {
			'POST' => $this->handlePost($request, $response),
			'GET' => $this->handleGet($request, $response),
			default => $response->withHeader('Location', '/')->withStatus(302)
		};
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
		$user = $this->session->getData('user');
		if($user === null) {
			$response->withHeader('Location', '/login')->withStatus(302);
		}
		$total_count = $this->total->getTotalCounts();
		$tools = $this->tool->getAllTools();
		try {
			$body = $this->view->render('index.twig', ['tools' => $tools, 'total' => $total_count, 'user' => $user]);
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
		// TODO: Implement handlePost() method.
		return $response;
	}

}