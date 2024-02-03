<?php
declare(strict_types = 1);
/*
Date: 28.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Parser\phpQuery\phpQueryParts;

use Cpsync\Parser\phpQuery\phpQueryObject;
use Cpsync\Parser\phpQuery;

trait Document
{


	/**
	 * @throws \Exception
	 */
	public static function selectDocument($id): void
	{
		$id = self::getDocumentID($id);
		phpQuery::debug("Selecting document '$id' as default one");
		phpQuery::$defaultDocumentID = self::getDocumentID($id);
	}


	/**
	 * @throws \Exception
	 */
	public static function unloadDocuments($id = null): void
	{
		if(isset($id)) {
			if($id = self::getDocumentID($id))
				unset(phpQuery::$documents[$id]);
		} else {
			foreach(phpQuery::$documents as $key => $val) {
				unset(phpQuery::$documents[$key]);
			}
		}
	}


	/**
	 * @throws \Exception
	 */
	public static function getDocument($id = null): phpQueryObject
	{
		if($id) {
			self::selectDocument($id);
		} else {
			$id = phpQuery::$defaultDocumentID;
		}
		return new phpQueryObject($id);
	}

	/**
	 * @throws \Exception
	 */
	public static function newDocument($markup = null, $content_type = null): phpQueryObject
	{
		if(!$markup)
			$markup = '';
		$document_id = phpQuery::createDocWrapper($markup, $content_type);
		return new phpQueryObject($document_id);
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function newDocumentHTML($markup = null, $charset = null): phpQueryObject
	{
		$content_type = $charset ? ";charset=$charset" : '';
		return self::newDocument($markup, "text/html$content_type");
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function newDocumentXML($markup = null, $charset = null): phpQueryObject
	{
		$content_type = $charset ? ";charset=$charset" : '';
		return self::newDocument($markup, 'text/xml'.$content_type);
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function newDocumentXHTML($markup = null, $charset = null): phpQueryObject
	{
		$content_type = $charset ? ";charset=$charset" : '';
		return self::newDocument($markup, "application/xhtml+xml".$content_type);
	}

	/**
	 * @throws \Exception
	 */
	public static function newDocumentPHP($markup = null, $content_type = 'text/html'): phpQueryObject
	{
		// TODO pass charset to phpToMarkup if possible (use DOMDocWrapper function)
		$markup = self::phpToMarkup($markup);
		return self::newDocument($markup, $content_type);
	}

	/**
	 * @throws \Exception
	 */
	public static function newDocumentFile($file, $content_type = null): phpQueryObject
	{
		$document_id = phpQuery::createDocWrapper(file_get_contents($file), $content_type);
		return new phpQueryObject($document_id);
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function newDocumentFileHTML($file, $charset = null): phpQueryObject
	{
		$content_type = $charset ? ";charset=$charset" : '';
		return self::newDocumentFile($file, 'text/html'.$content_type);
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function newDocumentFileXML($file, $charset = null): phpQueryObject
	{
		$content_type = $charset ? ";charset=$charset" : '';
		return self::newDocumentFile($file, 'text/xml'.$content_type);
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function newDocumentFileXHTML($file, $charset = null): phpQueryObject
	{
		$content_type = $charset ? ";charset=$charset" : '';
		return self::newDocumentFile($file, "application/xhtml+xml".$content_type);
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function newDocumentFilePHP($file, $content_type = null): phpQueryObject
	{
		return self::newDocumentPHP(file_get_contents($file), $content_type);
	}


}