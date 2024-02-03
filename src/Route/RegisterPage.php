<?php
declare(strict_types = 1);
/***
 * Date 02.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Mapper\Const\Notice;
use Cpsync\Route\Interface\PageView;
use Exception;
use Cpsync\Database;
use Cpsync\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class RegisterPage
 * User registration page
 * @package Cpsync\Route
 */
class RegisterPage implements PageView
{
	/**
	 * @var Environment $view
	 */
	private Environment $view;

	/**
	 * @var Session $session
	 */
	private Session $session;

	/**
	 * @var Database $database
	 */
	private Database $database;

	/**
	 * @var mixed $userSession
	 */
	private mixed $userSession;

	/**
	 * ProfilePage constructor.
	 * @param Environment $view
	 * @param Session $session
	 * @param Database $database
	 */
	public function __construct(Environment $view, Session $session, Database $database)
	{
		$this->session = $session;
		$this->userSession = $this->session->getData('user');
		$this->database = $database;
		$this->view = $view;

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
		if($this->userSession['user_status'] !== 'superadmin') {
			return $response->withHeader('Location', '/')->withStatus(302);
		}
		if($request->getMethod() === 'POST') {
			$this->handlePost($request, $response);
			return $response->withHeader('Location', '/')->withStatus(302);
		} elseif($request->getMethod() === 'GET') {
			$this->handleGet($request, $response);
			return $response;
		}
		return $response->withHeader('Location', '/register')->withStatus(302);
	}

	/**
	 * User registration
	 * @param array $data
	 * @return bool
	 * @throws Exception
	 */
	public function register(array $data): bool
	{
		$this->checkData($data);
		$this->isUserExist($data);
		$connect = $this->database->getConnection()->prepare('INSERT INTO users (user_name, user_email, user_login, user_pass, user_status) VALUES (:user_name, :user_email, :user_login, :user_pass, :user_status)');
		$connect->execute(['user_name' => $data['user_name'], 'user_email' => $data['user_email'], 'user_login' => $data['user_login'], 'user_pass' => password_hash($data['user_pass'], PASSWORD_BCRYPT), 'user_status' => $data['user_status']]);
		$this->session->setData('user', ['user_name' => $data['user_name'], 'user_email' => $data['user_email'], 'user_login' => $data['user_login'], 'user_status' => 'user']);
		return true;
	}

	/**
	 * GET request handler
	 * @param $request
	 * @param $response
	 * @return ResponseInterface
	 * @throws Exception
	 */
	public function handleGet($request, $response): ResponseInterface
	{
		try {
			$body = $this->view->render('register.twig', ['message' => $this->session->flush('message'), 'form' => $this->session->flush('form')]);
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
		try {
			$this->register($params);
		} catch(Exception $exception) {
			$this->session->setData('message', $exception->getMessage());
			$this->session->setData('form', $params);
			$response->withHeader('Location', '/register')->withStatus(302);
		}
		$response->withHeader('Location', '/')->withStatus(302);
		return $response;
	}

	/**
	 * Data check for correctness
	 * @param array $data
	 * @return void
	 * @throws Exception
	 */
	private function checkData(array $data): void
	{
		if(empty($data['user_name']) || preg_match('#\W+#u', $data['user_name']) || is_numeric($data['user_name'])) {
			throw new Exception(__LINE__.': '.Notice::W_USERNAME_REQUIRED);
		}
		if(empty($data['user_login']) || preg_match('~\W+~', $data['user_login']) || is_numeric($data['user_login'])) {
			throw new Exception(__LINE__.': '.Notice::W_LOGIN_REQUIRED);
		}
		if(empty($data['user_pass']) || $data['user_pass'] !== $data['user_pass2']) {
			throw new Exception(__LINE__.': '.Notice::W_PASSWORD_NOT_MATCH);
		}
		if(empty($data['user_email']) || !filter_var($data['user_email'], FILTER_VALIDATE_EMAIL)) {
			throw new Exception(__LINE__.': '.Notice::W_EMAIL_REQUIRED);
		}
	}

	/**
	 * Check if user exists
	 * @throws Exception
	 */
	private function isUserExist(array $data): void
	{
		$data['user_login'] = strtolower($data['user_login']);
		$connect = $this->database->getConnection()->prepare('SELECT * FROM users WHERE user_email = :user_email OR user_login = :user_login');
		$connect->execute([':user_email' => $data['user_email'], ':user_login' => $data['user_login']]);
		$user = $connect->fetch();
		if(!empty($user)) {
			throw new Exception(__LINE__.': '.Notice::W_USER_LOGIN_EMEIL_EXISTS);
		}
	}
}