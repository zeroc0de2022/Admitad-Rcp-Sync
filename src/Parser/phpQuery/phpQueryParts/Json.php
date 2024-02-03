<?php
declare(strict_types = 1);
/*
Date: 28.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Parser\phpQuery\phpQueryParts;

trait Json
{
	/* @noinspection PhpUnused */
	public static function toJSON($data): bool|string
	{
		if(function_exists('json_encode'))
			return json_encode($data);
		return false;
	}

	public static function parseJSON($json)
	{
		if(function_exists('json_decode')) {
			$return = json_decode(trim($json), true);
			// json_decode and UTF8 issues
			if(isset($return))
				return $return;
		}
		return false;
	}
}