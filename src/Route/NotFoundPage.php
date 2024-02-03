<?php
declare(strict_types = 1);
/***
 * Date 02.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Route\Interface\PageView;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * Class NotFoundPage
 */
class NotFoundPage implements PageView
{
	/**
	 * @var Environment $view
	 */
	private Environment $view;

	/**
	 * NotFoundPage constructor.
	 * @param Environment $view
	 */
	public function __construct(Environment $view)
	{
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
		try {
			$body = $this->view->render('notfound.twig');
			$response->getBody()->write($body);
		} catch(LoaderError|RuntimeError|SyntaxError $exception) {
			throw new Exception($exception->getTraceAsString(), $exception->getCode(), $exception->getMessage());
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
		// TODO: Implement handlePost() method.
		return $response;
	}
}