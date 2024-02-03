<?php
declare(strict_types = 1);
/*
Date: 28.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Parser\phpQuery\phpQueryParts;

use Exception;
use Cpsync\Parser\phpQuery;

trait Browser
{
	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function browserGet($url, $callback, $param1 = null, $param2 = null, $param3 = null)
	{
		if(phpQuery::plugin('WebBrowser')) {
			$params = func_get_args();
			return phpQuery::callbackRun([phpQuery::$plugins, 'browserGet'], $params);
		}
		phpQuery::debug('WebBrowser plugin not available...');
		throw new Exception(__LINE__.': '.__METHOD__." -> WebBrowser plugin not available...");
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function browserPost($url, $data, $callback, $param1 = null, $param2 = null, $param3 = null)
	{
		if(phpQuery::plugin('WebBrowser')) {
			$params = func_get_args();
			return phpQuery::callbackRun([phpQuery::$plugins, 'browserPost'], $params);
		}
		throw new Exception(__LINE__.': '.__METHOD__." -> WebBrowser plugin not available...");
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function browser($ajaxSettings, $callback, $param1 = null, $param2 = null, $param3 = null)
	{
		if(phpQuery::plugin('WebBrowser')) {
			$params = func_get_args();
			return phpQuery::callbackRun([phpQuery::$plugins, 'browser'], $params);
		}
		throw new Exception(__LINE__.': '.__METHOD__." -> WebBrowser plugin not available...");
	}
}