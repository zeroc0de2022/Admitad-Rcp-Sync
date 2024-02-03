<?php
/*
Date: 14.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);

namespace Cpsync\Parser\phpQuery;

/* @noinspection PhpUnused */

class CallbackBody extends Callback
{
	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct($param_list, $code, $param1 = null, $param2 = null, $param3 = null)
	{
		$params = func_get_args();
		$params = array_slice($params, 2);
		$this->callback = function() use ($param_list, $code) {
			eval($code);
		};
		$this->params = $params;
	}
}