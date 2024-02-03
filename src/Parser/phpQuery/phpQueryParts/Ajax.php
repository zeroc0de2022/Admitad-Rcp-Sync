<?php
declare(strict_types = 1);
/*
Date: 28.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Parser\phpQuery\phpQueryParts;

use Cpsync\Parser\phpQuery;
use Exception;
use Cpsync\Parser\phpQuery\phpQueryObject;
use Cpsync\Parser\phpQuery\phpQueryEvents;

trait Ajax
{


	/* @noinspection PhpUnused */
	public static function ajaxSetup($options): void
	{
		phpQuery::$ajaxSettings = array_merge(phpQuery::$ajaxSettings, $options);
	}

	/* @noinspection PhpUnused */
	public static function ajaxAllowHost($host1, $host2 = null, $host3 = null): void
	{
		$loop = is_array($host1) ? $host1 : func_get_args();
		foreach($loop as $host) {
			if($host && !in_array($host, phpQuery::$ajaxAllowedHosts)) {
				phpQuery::$ajaxAllowedHosts[] = $host;
			}
		}
	}

	/* @noinspection PhpUnused */
	public static function ajaxAllowURL($url1, $url2 = null, $url3 = null): void
	{
		$loop = is_array($url1) ? $url1 : func_get_args();
		foreach($loop as $url)
			self::ajaxAllowHost(parse_url($url, PHP_URL_HOST));
	}

	/**
	 * @throws Exception
	 */
	public static function ajax($options = [], $xhr = null)
	{
		$result = self::ajaxPart0($options, $xhr);
		[$document_id, $client] = $result;
		// JSONP
		$jsre = '/=\\?(&|$)/';
		self::ajaxPart1($options, $jsre);
		self::ajaxPart2($options, $jsre);
		$client = self::ajaxPart3($options, $client);
		self::ajaxPart4($options, $client, $document_id);
		return self::ajaxPart5($options, $client, $document_id);
	}

	/**
	 * @throws \Exception
	 */
	public static function ajaxPart0(&$options, $xhr): array
	{
		$options = array_merge(phpQuery::$ajaxSettings, $options);
		$document_id = isset($options['document']) ? self::getDocumentID($options['document']) : null;
		$client = null;
		if($xhr) {
			// reuse existing XHR object, but clean it up
			$client = $xhr;
			$client->setAuth(false);
			$client->setHeaders('If-Modified-Since', null);
			$client->setHeaders('Referer', null);
			$client->resetParameters();
		}
		if(isset($options['timeout']) && isset($client)) {
			$client->setConfig(['timeout' => $options['timeout']]);
		}
		foreach(phpQuery::$ajaxAllowedHosts as $key => $host)
			if($host == '.' && isset($_SERVER['HTTP_HOST']))
				phpQuery::$ajaxAllowedHosts[$key] = $_SERVER['HTTP_HOST'];
		$host = parse_url($options['url'], PHP_URL_HOST);
		if(!in_array($host, phpQuery::$ajaxAllowedHosts)) {
			throw new Exception(__LINE__.': '.__METHOD__.' -> Request not permitted, host `'.$host.'` not present in phpQuery::$ajaxAllowedHosts');
		}
		return [$document_id, $client];
	}

	public static function ajaxPart1(&$options, $jsre): void
	{
		if(isset($options['dataType']) && $options['dataType'] == 'jsonp') {
			$jsonp_callback_param = $options['jsonp'] ?? 'callback';
			if(strtolower($options['type']) == 'get') {
				if(!preg_match($jsre, $options['url'])) {
					$sep = strpos($options['url'], '?') ? '&' : '?';
					$options['url'] .= $sep.$jsonp_callback_param."=?";
				}
			} elseif($options['data']) {
				$jsonp = false;
				foreach($options['data'] as $key => $val) {
					unset($key);
					if($val == '?')
						$jsonp = true;
				}
				if(!$jsonp) {
					$options['data'][$jsonp_callback_param] = '?';
				}
			}
			$options['dataType'] = 'json';
		}

	}

	/**
	 * @param array $options
	 * @param $jsre
	 * @return void
	 */
	public static function ajaxPart2(array &$options = [], $jsre = null): void
	{
		if(isset($options['dataType']) && $options['dataType'] == 'json') {
			$jsonp_callback = 'json_'.md5(microtime());
			$jsonp_data = $jsonp_url = false;
			if($options['data']) {
				foreach($options['data'] as $key => $val) {
					if($val == '?')
						$jsonp_data = $key;
				}
			}
			if(preg_match($jsre, $options['url']))
				$jsonp_url = true;
			if($jsonp_data !== false || $jsonp_url) {
				// remember callback name for httpData()
				$options['_jsonp'] = $jsonp_callback;
				if($jsonp_data !== false)
					$options['data'][$jsonp_data] = $jsonp_callback;
				if($jsonp_url)
					$options['url'] = preg_replace($jsre, "=$jsonp_callback\\1", $options['url']);
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	public static function ajaxPart3($options, $client): mixed
	{
		if(!isset($client)) {
			throw new Exception(__LINE__.': '.__METHOD__.' -> Client is null');
		}
		$client->setUri($options['url']);
		$client->setMethod(strtoupper($options['type']));
		if(isset($options['referer']) && $options['referer'])
			$client->setHeaders('Referer', $options['referer']);
		$client->setHeaders(['User-Agent' => 'Mozilla/5.0 (X11; U; Linux x86; en-US; rv:1.9.0.5) Gecko'.'/2008122010 Firefox/3.0.5', // TODO custom charset
			'Accept-Charset' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7', 'Accept-Language' => 'en-us,en;q=0.5',]);
		if($options['username'])
			$client->setAuth($options['username'], $options['password']);
		if(isset($options['ifModified']) && $options['ifModified'])
			$client->setHeaders('If-Modified-Since', phpQuery::$lastModified ?? 'Thu, 01 Jan 1970 00:00:00 GMT');
		$client->setHeaders('Accept', isset($options['dataType']) && isset(phpQuery::$ajaxSettings['accepts'][$options['dataType']]) ? phpQuery::$ajaxSettings['accepts'][$options['dataType']].', */*' : phpQuery::$ajaxSettings['accepts']['_default']);

		// TODO $options['processData']
		if($options['data'] instanceof phpQueryObject) {
			$serialized = $options['data']->serializeArray($options['data']);
			$options['data'] = [];
			foreach($serialized as $val)
				$options['data'][$val['name']] = $val['value'];
		}
		if(strtolower($options['type']) == 'get') {
			$client->setParameterGet($options['data']);
		} elseif(strtolower($options['type']) == 'post') {
			$client->setEncType($options['contentType']);
			$client->setParameterPost($options['data']);
		}
		return $client;
	}

	/**
	 * @throws \Exception
	 */
	public static function ajaxPart4($options, $client, $document_id): void
	{
		if(phpQuery::$active == 0 && $options['global'])
			phpQueryEvents::trigger($document_id, 'ajaxStart');
		phpQuery::$active++;
		// beforeSend callback
		if(isset($options['beforeSend']) && $options['beforeSend'])
			phpQuery::callbackRun($options['beforeSend'], [$client]);
		// ajaxSend event
		if($options['global'])
			phpQueryEvents::trigger($document_id, 'ajaxSend', [$client, $options]);
		if(phpQuery::$debug) {
			phpQuery::debug($options['type'].': '.$options['url'].'\n');
			phpQuery::debug("Options: <pre>".var_export($options, true)."</pre>\n");
		}
	}

	/**
	 * @throws \Exception
	 */
	public static function ajaxPart5($options, $client, $document_id): mixed
	{
		// request
		$response = $client->request();
		if(phpQuery::$debug) {
			phpQuery::debug('Status: '.$response->getStatus().' / '.$response->getMessage());
			phpQuery::debug($client->getLastRequest());
			phpQuery::debug($response->getHeaders());
		}
		if($response->isSuccessful()) {
			// XXX tempolary
			phpQuery::$lastModified = $response->getHeader('Last-Modified');
			$data = phpQuery::httpData($response->getBody(), $options['dataType'], $options);
			if(isset($options['success']) && $options['success'])
				phpQuery::callbackRun($options['success'], [$data, $response->getStatus(), $options]);
			if($options['global'])
				phpQueryEvents::trigger($document_id, 'ajaxSuccess', [$client, $options]);
		} else {
			if(isset($options['error']) && $options['error'])
				phpQuery::callbackRun($options['error'], [$client, $response->getStatus(), $response->getMessage()]);
			if($options['global'])
				phpQueryEvents::trigger($document_id, 'ajaxError', [$client, $response->getMessage(), $options]);/*$response->getStatus(),*/
		}
		if(isset($options['complete']) && $options['complete'])
			phpQuery::callbackRun($options['complete'], [$client, $response->getStatus()]);
		if($options['global'])
			phpQueryEvents::trigger($document_id, 'ajaxComplete', [$client, $options]);
		if($options['global'] && !--phpQuery::$active)
			phpQueryEvents::trigger($document_id, 'ajaxStop');
		return $client;
	}

}