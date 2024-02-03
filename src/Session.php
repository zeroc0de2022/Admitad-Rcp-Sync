<?php
declare(strict_types = 1);
/***
 * Date 01.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


/**
 * Class Session
 */
class Session
{

	/**
	 * Middleware for working with session
	 * @param ServerRequestInterface $request
	 * @param RequestHandlerInterface $handler
	 * @return ResponseInterface
	 */
	public function sessionInit(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$this->start();
		$response = $handler->handle($request);
		$this->save();
		return $response;
	}

	/**
	 * Session start
	 * @return void
	 */
	public function start(): void
	{
		session_start();
	}

	/**
	 * Save data to session
	 * @param string $key
	 * @param $value
	 * @return void
	 */
	public function setData(string $key, $value): void
	{
		$_SESSION[$key] = $value;
	}

	/**
	 * Get data from session and if it is empty, then return null
	 * @param string $key
	 * @return mixed|null
	 */
	public function getData(string $key): mixed
	{
		return !empty($_SESSION[$key]) ? $_SESSION[$key] : null;
	}

	/**
	 * Get session and if it is empty, then return null
	 * @return array|null
	 */
	public function getSession(): ?array
	{
		return !empty($_SESSION) ? $_SESSION : null;
	}

	/**
	 * Save session
	 * @return void
	 */
	public function save(): void
	{
		session_write_close();
	}

	/**
	 * Get and delete data from session
	 * @param string $key
	 * @return mixed|null
	 */
	public function flush(string $key): mixed
	{
		$value = $this->getData($key);
		$this->unset($key);
		return $value;
	}

	/**
	 * Delete data from session
	 * @param string $key
	 * @return void
	 */
	private function unset(string $key): void
	{
		unset($_SESSION[$key]);
	}
}