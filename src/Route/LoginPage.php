<?php
declare(strict_types = 1);
/***
 * Date 02.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Mapper\Login;
use Cpsync\Route\Interface\PageView;
use Twig\Environment;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class LoginPage
 */
class LoginPage implements PageView
{
	/**
	 * @var Environment $view
	 */
	public Environment $view;

	/**
	 * @var Login $login
	 */
	private Login $login;

	private object $session;

	/**
	 * LoginPage constructor.
	 * @param Login $login
	 * @param Environment $view
	 */
	public function __construct(Login $login, Environment $view)
	{
		$this->login = $login;
		$this->session = $this->login->session;
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
		if($this->session->getData('user') !== null) {
			return $response->withHeader('Location', '/')->withStatus(302);
		}
		if($request->getMethod() === 'POST') {
			return $this->handlePost($request, $response);
		} elseif($request->getMethod() === 'GET') {
			return $this->handleGet($request, $response);
		}
		return $response->withHeader('Location', '/login')->withStatus(302);
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
			$this->login->loginPost($params['user_login'], $params['user_pass']);
		} catch(Exception $exception) {
			$this->login->session->setData('message', $exception->getMessage());
			$this->login->session->setData('form', $params);
			return $response->withHeader('Location', '/login')->withStatus(302);
		}
		return $response->withHeader('Location', '/')->withStatus(302);
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
			$body = $this->view->render('login.twig', ['message' => $this->login->session->flush('message'), 'form' => $this->login->session->flush('form')]);
		} catch(LoaderError|SyntaxError|RuntimeError $exception) {
			throw new Exception(__LINE__.': '.$exception->getMessage());
		}
		$response->getBody()->write($body);
		return $response;
	}

	/***
	 * Logout request handler
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$this->login->logout();
		return $response->withHeader('Location', '/')->withStatus(302);
	}

}