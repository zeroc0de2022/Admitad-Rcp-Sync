<?php
declare(strict_types = 1);
/*
Date: 25.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Mapper\Trait;

/**
 * Trait Message
 * @package Cpsync\Mapper\Trait
 * @property array $message
 */
trait Message
{
	/**
	 * @var array $message
	 */
	public array $message;

	/**
	 * Set message
	 * @param string $message
	 * @param bool $status
	 * @return mixed
	 */
	public function setMessage(string $message, bool $status = false): mixed
	{
		$this->message['message'] = strlen($message)>2
			? $message
			: $this->message['message'];
		$this->message['status'] = $status;
		return null;
	}


	/**
	 * Return message in json format
	 * @return bool|string
	 */
	public function getMessageJson(): bool|string
	{
		return json_encode($this->message, JSON_UNESCAPED_UNICODE);
	}


}