<?php
declare(strict_types = 1);
/***
 * Date 02.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Database;
use Cpsync\Mapper\Tool;
use Cpsync\Session;
use Twig\Environment;
use Exception;
use Cpsync\Route\Interface\PageView;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Cpsync\Mapper\Trait\BlockUser;

/**
 * Class ImporterPage
 */
class ImporterPage implements PageView
{
	use BlockUser;

	/**
	 * @var Environment $view
	 */
	private Environment $view;

	/**
	 * @var Database $database
	 */
	private Database $database;

	/**
	 * @var Session $session
	 */
	private Session $session;

	public mixed $userSession;

	private Tool $tool;

	/**
	 * @param Session $session
	 * @param Environment $view
	 * @param Database $database
	 * @param \Cpsync\Mapper\Tool $tool
	 * @throws \Exception
	 */
	public function __construct(Session $session, Environment $view, Database $database, Tool $tool)
	{
		$this->database = $database;
		$this->tool = $tool;
		$this->view = $view;
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
		if($request->getMethod() === 'GET') {
			$this->handleGet($request, $response);
		}
		return $response;
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
		if($user == null) {
			return $response->withHeader('Location', '/login')->withStatus(302);
		}
		try {
			$tools = $this->tool->getAllTools();
			$body = $this->view->render('importer.twig', ['tools' => $tools, 'user' => $user]);
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

	/**
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function relocate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		return $response->withHeader('Location', '/importer')->withStatus(302);
	}


}