<?php
declare(strict_types = 1);
/***
 * Date 24.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */

namespace Cpsync\Parser\phpParser;

use CurlHandle;
use CurlMultiHandle;
use Exception;

/**
 * Class MultiCurl
 * @package Cpsync\Parser\phpParser
 */
class MultiCurl
{
	/**
	 * Request curl handle
	 * @var array
	 */
	public array $curlHandles = [];

	/**
	 * Curl multi handle
	 * @var CurlMultiHandle $curlMultiHandle
	 */
	private CurlMultiHandle $curlMultiHandle;

	/**
	 * MultiCurl constructor.
	 */
	public function __construct()
	{
		$this->curlMultiHandle = curl_multi_init();
	}

	/**
	 * Add curl handle
	 * @param int $handle_id
	 * @param CurlHandle $curl_handle
	 * @return void
	 */
	public function addCurlHandle(int $handle_id, CurlHandle $curl_handle): void
	{
		$this->curlHandles[$handle_id] = $curl_handle;
		curl_multi_add_handle($this->curlMultiHandle, $curl_handle);
	}

	/**
	 * Remove curl handle
	 * @return void
	 */
	public function __destruct()
	{
		foreach($this->curlHandles as $handle_id => $curl_handle) {
			unset($handle_id);
			$this->removeHandle($curl_handle);
		}
		curl_multi_close($this->curlMultiHandle);
	}


	/**
	 * Get content from curl handle
	 * @param CurlHandle $curl_handle
	 * @return string|null
	 */
	public function getHandleContent(CurlHandle $curl_handle): ?string
	{
		return curl_multi_getcontent($curl_handle);
	}

	/**
	 * Remove curl handle
	 * @param CurlHandle $curl_handle
	 * @return void
	 */
	public function removeHandle(CurlHandle $curl_handle): void
	{
		curl_multi_remove_handle($this->curlMultiHandle, $curl_handle);
	}

	/***
	 * Curl multi threads handle
	 * @return void
	 * @throws Exception
	 */
	public function threadAll(): void
	{
		$mh = $this->curlMultiHandle;
		$still_running = 0;
		// execute the handles
		do {
			$mrc = curl_multi_exec($mh, $still_running);
		} while($mrc == CURLM_CALL_MULTI_PERFORM);
		// Loop while running
		while($still_running && $mrc == CURLM_OK) {
			if(curl_multi_select($mh) != -1) {
				do {
					$mrc = curl_multi_exec($mh, $still_running);
				} while($mrc == CURLM_CALL_MULTI_PERFORM);
			}
		}
	}


}