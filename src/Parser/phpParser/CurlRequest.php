<?php
declare(strict_types = 1);
/***
 * Date 22.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Parser\phpParser;

use Cpsync\Mapper\Const\Notice;

/**
 * @package Cpsync\Parser\phpParser
 */
class CurlRequest
{

	/**
	 * CURL settings received from the user
	 * @var array
	 */
	private array $settings;

	/**
	 * CURL request options
	 * @var array
	 */
	private array $curlOptions;

	/**
	 * CURL options by default
	 * @return void
	 */
	private function init(): void
	{
		$this->curlOptions = [// Common options
			CURLOPT_FAILONERROR => true, // return false if response code >= 400
			CURLOPT_RETURNTRANSFER => 1, // return result of request
			CURLOPT_SSL_VERIFYPEER => false, // not verify SSL certificate
			CURLOPT_SSL_VERIFYHOST => false, // not verify SSL certificate Host
		];
	}

	/**:
	 * For sending Curl request and getting result
	 * @param array $settings
	 * @return array
	 */
	public function pRequest(array $settings): array
	{
		$this->init();
		$this->settings = $settings;
		// Prepare CURL options
		foreach($this->settings as $key => $value) {
			$this->curlOptions += $this->getCurlOption($key, $value);
		}
		// Start cURL session
		$ch = curl_init();
		// Applying options
		curl_setopt_array($ch, $this->curlOptions);
		// Execution and Result processing
		$response = $this->handleError($ch);
		if(!is_array($response)) {
			$response = $this->curlResponse($response, $ch);
		}
		return $response;
	}

	/**
	 * For sending and handling possible CURL request errors
	 * @param $ch - curl handler
	 * @return string|array|bool
	 */
	private function handleError($ch): string|array|bool
	{
		$response = curl_exec($ch);
		// Error handler
		$errno = curl_errno($ch);
		if($errno) {
			$error_message = curl_error($ch);
			error_log($error_message);
			return ['status' => false, 'body' => Notice::E_URL.': '.$error_message];
		}
		// Empty response handler
		if($response === false)
			return ['status' => false, 'body' => Notice::E_URL.': '.curl_error($ch)];
		if(strlen($response) < 1)
			return ['status' => false, 'body' => Notice::W_EMPTY_RESPONSE];
		// Close connection
		curl_close($ch);
		// Set encoding
		if(isset($this->settings['iconv'])) {
			$from = $this->settings['iconv']['from'];
			$to = $this->settings['iconv']['to'];
			$response = iconv($from, $to, $response);
		}
		return $response;
	}

	/***
	 * Prepare return array with final results of request execution
	 * @param $response - response from server
	 * @param $ch - curl handler
	 * @return array
	 */
	private function curlResponse($response, $ch): array
	{
		// Headers processing
		$headers = '';
		if(isset($this->settings['header']) && $this->settings['header'] == 1) {
			[$headers] = explode('\r\n\r\n', $response);
		}
		// Remove headers from response
		$data = str_replace($headers, '', $response);
		return [
			'status' => true,
			'headers' => $headers,
			'body' => trim($data),
			'ch' => $ch];
	}

	/**
	 * Returns the specified option for CURL
	 * @param $key -key of option
	 * @param $value -value of option
	 * @return array
	 */
	private function getCurlOption($key, $value): array
	{
		return match ($key) {
			'url' =>        [CURLOPT_URL            => $value], // request url
			'ua' =>         [CURLOPT_USERAGENT      => $value], // useragent
			'session' =>    [CURLOPT_COOKIESESSION  => $value], // new session
			'cookie' =>     [CURLOPT_COOKIE         => $value], // cookie
			'cookieFile' => [CURLOPT_COOKIEJAR      => $value,  // cookie file
							CURLOPT_COOKIEFILE      => $value], // cookie file
			'header' =>     [CURLOPT_HEADER         => $value], // return headers
			'headers' =>    [CURLOPT_HTTPHEADER     => $value], // set headers
			'referer' =>    [CURLOPT_REFERER        => $value], // referer
			'post' =>       [CURLOPT_POSTFIELDS     => $value,  // post data
							CURLOPT_POST            => true],   // post request
			'nobody' =>     [CURLOPT_NOBODY         => $value], // return only headers
			'return' =>     [CURLOPT_RETURNTRANSFER => $value], // return result
			'follow' =>     [CURLOPT_FOLLOWLOCATION => $value], // follow redirects

			'timeout' => [CURLOPT_TIMEOUT => $value], // timeout in seconds
			'timeout_mc' => [CURLOPT_TIMEOUT_MS => $value], // timeout in ms
			'proxy' => $this->proxyForCurl($this->settings['proxy']), // proxy
			default => []
		};
	}

	/**
	 * Returns an array with proxy options for CURL
	 * @param $proxy - proxy settings
	 * @return array
	 */
	private function proxyForCurl(array $proxy): array
	{
		$output = [];
		// prepare proxy
		if(isset($proxy['ip'])) {
			$proxy_parts = explode(':', $proxy['ip']);
			$output = [CURLOPT_PROXY => $proxy_parts[0].':'.$proxy_parts[1], CURLOPT_PROXYTYPE => constant('CURLPROXY_'.strtoupper($proxy['type'])) ?? CURLPROXY_HTTP];
			if(isset($proxy_parts[2], $proxy_parts[3])) {
				$output[CURLOPT_PROXYUSERPWD] = $proxy_parts[2].':'.$proxy[3];
			}
		}
		return $output;
	}


}