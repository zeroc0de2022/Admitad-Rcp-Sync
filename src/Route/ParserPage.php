<?php
declare(strict_types = 1);
/***
 * Date 06.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Mapper\Parser;
use Cpsync\Route\Interface\PageView;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ParserPage
 */
class ParserPage implements PageView
{
	/**
	 * @var Parser $parser
	 */
	private Parser $parser;

	/**
	 * ParserPage constructor.
	 * @param Parser $parser
	 */
	public function __construct(Parser $parser)
	{
		$this->parser = $parser;
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
		return match ($request->getMethod()) {
			'POST' => $this->handlePost($request, $response),
			'GET' => $this->handleGet($request, $response),
			default => $response->withHeader('Location', '/login')->withStatus(302)
		};
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
		$params = $request->getQueryParams();
		if(isset($params['action'], $params['tool_name'])) {
			try {
				$this->parser->init($params);
			} catch(Exception $exception) {
				print($exception->getMessage());
			}
		}
		return $response;
	}


}