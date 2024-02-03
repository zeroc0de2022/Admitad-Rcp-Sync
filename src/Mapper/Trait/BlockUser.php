<?php
declare(strict_types = 1);
/*
Date: 26.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Mapper\Trait;

/**
 * Trait BlockUser
 * @package Cpsync\Mapper\Trait
 */
trait BlockUser
{
	/**
	 * Check if user is banned
	 * @return void
	 * @throws \Exception
	 */
	public function isBanned(): void
	{
		if(!isset($this->userSession['user_id'])) {
			header('Location: /login');
		}
		if($this->userSession['user_status'] == 'banned') {
			header('Location: /banned');
		}
	}

}