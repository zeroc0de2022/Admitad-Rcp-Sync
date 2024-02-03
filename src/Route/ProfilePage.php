<?php
declare(strict_types = 1);
/***
 * Date 02.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Mapper\Profile;
use Cpsync\Mapper\Tool;
use Cpsync\Route\Interface\PageView;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Cpsync\Mapper\Trait\BlockUser;
use Cpsync\Database;

/**
 * Class ProfilePage
 * @package Cpsync\Route
 */
class ProfilePage implements PageView
{
	use BlockUser;

	/**
	 * @var Environment $view
	 */
	private Environment $view;

	/**
	 * @var mixed $session
	 */
	private mixed $session;

	/**
	 * @var array|null $userSession
	 * @psalm-var array{id: int, username: string, email: string, password: string, role: string}|null
	 */
	public mixed $userSession;

	/**
	 * @var Database $database
	 */
	private Database $database;

	/**
	 * @var Profile $profile
	 */
	private Profile $profile;

	/**
	 * @var Tool $tool
	 */
	private Tool $tool;

	/**
	 * ProfilePage constructor.
	 * @param Environment $view
	 * @param Profile $profile
	 * @param \Cpsync\Mapper\Tool $tool
	 * @throws \Exception
	 */
	public function __construct(Environment $view, Profile $profile, Tool $tool)
	{

		$this->profile = $profile;
		$this->database = $this->profile->database;
		$this->tool = $tool;
		$this->view = $view;
		$this->session = $this->profile->session;
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
		if($request->getMethod() === 'POST') {
			$this->handlePost($request, $response);
		} elseif($request->getMethod() === 'GET') {
			$this->handleGet($request, $response);
			return $response;
		}
		return $response->withHeader('Location', '/profile')->withStatus(302);
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
		try {
			$tools = $this->tool->getAllTools();
			$profile = $this->profile->getProfile($this->userSession['user_login']);
			$body = $this->view->render('profile.twig', ['profile' => $profile, 'tools' => $tools, 'user' => $this->userSession, 'code' => $this->session->flush('code'), 'message' => $this->session->flush('message'), 'form' => $this->session->flush('form')]);
		} catch(LoaderError|SyntaxError|RuntimeError $exception) {
			throw new Exception($exception->getMessage());
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
		try {
			$this->profile->updateProfile($params);
		} catch(Exception $exception) {
			$this->session->setData('message', $exception->getMessage());
			$this->session->setData('code', $exception->getCode());
			$this->session->setData('form', $params);
		}
		$body = $this->view->render('profile.twig', ['user' => $this->userSession, 'code' => $this->session->flush('code'), 'message' => $this->session->flush('message'), 'form' => $this->session->flush('form')]);
		$response->getBody()->write($body);
		return $response;
	}
}