<?php
declare(strict_types = 1);
/***
 * Date 02.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Route;

use Cpsync\Mapper\Tool;
use Cpsync\Mapper\User;
use Cpsync\Route\Interface\PageView;
use Cpsync\Session;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class VerifyPage
 */
class VerifyPage implements PageView
{
	/**
	 * @var Session $session
	 */
	private Session $session;

	/**
	 * @var User $user
	 */
	private User $user;

	/**
	 * @var Tool $tool
	 */
	private Tool $tool;


	/**
	 * VerifyPage constructor.
	 * @param Session $session
	 * @param User $user
	 * @param Tool $tool
	 */
	public function __construct(Session $session, User $user, Tool $tool)
	{
		$this->user = $user;
		$this->tool = $tool;
		$this->session = $session;
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
		match ($request->getMethod()) {
			'POST' => $this->handlePost($request, $response),
			'GET' => $this->handleGet($request, $response),
			default => $response
		};
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
		//TODO: handleGet method
		return $response;
	}

	/**
	 * POST request handler
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 * @throws \Exception
	 */
	public function handlePost(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
	{
		$params = (array)$request->getParsedBody();
		$params = $this->editParamsKey($params, 'modal_');
		$is_exist = ['status' => false];
		if(count($params) == 1) {
			$key = key($params);
			switch($key) {
				case 'tool_name':
				case 'remote_link':
					{
						$result = $this->tool->getTool($key, $params[$key]);
					}
					break;
				case 'user_login':
				case 'user_email':
					{
						$result = $this->user->getUser($key, $params[$key]);
					}
					break;
				default:
					$is_exist['status'] = false;
			}
			if(isset($result[$key]) && $result[$key] == $params[$key]) {
				$is_exist['status'] = true;
			}

			print_r(json_encode($is_exist));
		}
		return $response;
	}

	/**
	 * Change array keys
	 * @param array $params
	 * @param string $replace
	 * @return array
	 */
	private function editParamsKey(array $params, string $replace = ''): array
	{
		// Use a callback function on each array element with a key change
		array_walk($params, function($value, $old_key) use (&$params, $replace) {
			$new_key = str_replace($replace, '', $old_key);
			unset($params[$old_key]); // Optionally: delete the old key if necessary
			$params[$new_key] = $value;
		});
		return $params;
	}
}