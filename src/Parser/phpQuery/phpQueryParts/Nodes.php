<?php
declare(strict_types = 1);
/*
Date: 28.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Parser\phpQuery\phpQueryParts;

use Cpsync\Parser\phpQuery;

trait Nodes
{
	protected static function dataSetupNode($node, $document_id)
{
		// search are return if already exists
		foreach(phpQuery::$documents[$document_id]->dataNodes as $data_node) {
			if($node->isSameNode($data_node))
				return $data_node;
		}
		// if doesn't, add it
		phpQuery::$documents[$document_id]->dataNodes[] = $node;
		return $node;
	}
	protected static function dataRemoveNode($node, $document_id): void
	{
		// search are return if already exists
		foreach(phpQuery::$documents[$document_id]->dataNodes as $key => $data_node) {
			if($node->isSameNode($data_node)) {
				unset(phpQuery::$documents[$document_id]->dataNodes[$key]);
				unset(phpQuery::$documents[$document_id]->data[$data_node->dataID]);
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	public static function data($node, $name, $data, $document_id = null)
	{
		if(!$document_id)
			// TODO check if this works
			$document_id = self::getDocumentID($node);
		$document = phpQuery::$documents[$document_id];
		$node = self::dataSetupNode($node, $document_id);
		if(!isset($node->dataID))
			$node->dataID = ++phpQuery::$documents[$document_id]->uuid;
		$id = $node->dataID;
		if(!isset($document->data[$id]))
			$document->data[$id] = [];
		if(!is_null($data))
			$document->data[$id][$name] = $data;
		if($name) {
			if(isset($document->data[$id][$name]))
				return $document->data[$id][$name];
		}
		return $id;
	}

	/**
	 * @throws \Exception
	 */
	public static function removeData($node, $name, $document_id): void
	{
		if(!$document_id)
			// TODO check if this works
			$document_id = self::getDocumentID($node);
		$document = phpQuery::$documents[$document_id];
		$node = self::dataSetupNode($node, $document_id);
		$id = $node->dataID;
		if($name) {
			if(isset($document->data[$id][$name]))
				unset($document->data[$id][$name]);
			$name = null;
			foreach($document->data[$id] as $name)
				break;
			if(!$name)
				self::removeData($node, $name, $document_id);
		}
		else {
			self::dataRemoveNode($node, $document_id);
		}
	}
}