<?php
declare(strict_types = 1);
/*
Date: 28.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Parser\phpQuery\phpQueryParts;

use DOMDocument;
use Cpsync\Parser\phpQuery;
use DOMNode;
use Cpsync\Parser\phpQuery\phpQueryObject;
use Exception;

trait Getter
{
	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function getJSON($url, $data = null, $callback = null)
	{
		if(!is_array($data)) {
			$callback = $data;
			$data = null;
		}
		// TODO some array_values on this shit
		return self::ajax(['type' => 'GET', 'url' => $url, 'data' => $data, 'success' => $callback, 'dataType' => 'json']);
	}


	/**
	 * @throws \Exception
	 */
	public static function get($url, $data = null, $callback = null, $type = null)
	{
		if(!is_array($data)) {
			$callback = $data;
			$data = null;
		}
		// TODO some array_values on this shit
		return self::ajax(['type' => 'GET', 'url' => $url, 'data' => $data, 'success' => $callback, 'dataType' => $type]);
	}

	/**
	 * @throws \Exception
	 */
	public static function post($url, $data = null, $callback = null, $type = null)
	{
		if(!is_array($data)) {
			$callback = $data;
			$data = null;
		}
		return self::ajax(['type' => 'POST', 'url' => $url, 'data' => $data, 'success' => $callback, 'dataType' => $type]);
	}


	/**
	 * @throws \Exception
	 */
	public static function getDocumentID($source)
	{
		if($source instanceof DOMDocument) {
			foreach(phpQuery::$documents as $id => $document) {
				if($source->isSameNode($document->document))
					return $id;
			}
		} elseif($source instanceof DOMNode) {
			foreach(phpQuery::$documents as $id => $document) {
				if($source->ownerDocument->isSameNode($document->document))
					return $id;
			}
		} elseif($source instanceof phpQueryObject) {
			return $source->getDocumentID();
		} elseif(is_string($source) && isset(phpQuery::$documents[$source])) {
			return $source;
		}
		throw new Exception(__LINE__.': '.__METHOD__.' -> Invalid argument in getDocumentID()');
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function getDOMDocument($source)
	{
		$id = end(phpQuery::$documents);
		if($source instanceof DOMDocument)
			return $source;
		$source = self::getDocumentID($source);
		return $source ? phpQuery::$documents[$id]['document'] : null;
	}
}