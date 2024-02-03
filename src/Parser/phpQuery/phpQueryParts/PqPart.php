<?php
declare(strict_types = 1);
/*
Date: 28.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Parser\phpQuery\phpQueryParts;

use DOMNode;
use Cpsync\Parser\phpQuery;
use DOMDocument;
use Exception;
use DOMNodeList;
use Cpsync\Parser\phpQuery\phpQueryObject;

trait PqPart
{


	/**
	 * @throws \Exception
	 */
	public static function pq($arg1, $context = null)
{

		$context = self::pqPart1($arg1, $context);
		$dom_id = self::pqPart2($context);
		return self::pqPart3($arg1, $dom_id, $context);

	}

	public static function pqPart1($arg1, $context = null)
	{
		if($arg1 instanceof DOMNode && !isset($context)) {
			foreach(phpQuery::$documents as $document_wrapper) {
				$compare = $arg1 instanceof DOMDocument ? $arg1 : $arg1->ownerDocument;
				if($document_wrapper->document->isSameNode($compare))
					$context = $document_wrapper->docid;
			}
		}
		return $context;
	}

	/**
	 * @throws \Exception
	 */
	public static function pqPart2($context)
	{
		if(!$context) {
			$dom_id = phpQuery::$defaultDocumentID;
			if(!$dom_id) {
				throw new Exception(__LINE__.': '.__METHOD__." -> Can't use default DOM, because there isn't any. Use self::newDocument() first.");
			}
		} elseif($php_query_obj_bool = $context instanceof phpQueryObject) {
			unset($php_query_obj_bool);
			$dom_id = $context->getDocumentID();
		} elseif($dom_document_bool = $context instanceof DOMDocument) {
			unset($dom_document_bool);
			$dom_id = self::getDocumentID($context);
			if(!$dom_id) {
				//throw new Exception(__LINE__.': '.__METHOD__." -> Orphaned DOMDocument");
				$dom_id = self::newDocument($context)->getDocumentID();
			}
		} elseif($dom_node_bool = $context instanceof DOMNode) {
			unset($dom_node_bool);
			$dom_id = self::getDocumentID($context);
			if(!$dom_id) {
				throw new Exception(__LINE__.': '.__METHOD__.' -> Orphaned DOMNode');
			}
		} else {
			$dom_id = $context;
		}
		return $dom_id;
	}


	/**
	 * @throws \Exception
	 */
	public static function pqPart3($arg1, $dom_id, $context)
	{
		if($arg1 instanceof phpQueryObject) {
			//		if(is_object($arg1) && (get_class($arg1) == 'phpQueryObject' || $arg1 instanceof PHPQUERY || is_subclass_of($arg1, 'phpQueryObject'))) {
			/**
			 * Return $arg1 or import $arg1 stack if document differs:
			 * pq(pq('<div/>'))
			 */
			if($arg1->getDocumentID() == $dom_id)
				return $arg1;
			$class = get_class($arg1);
			// support inheritance by passing old object to overloaded constructor
			$php_query = $class != 'phpQuery' ? new $class($arg1, $dom_id) : new phpQueryObject($dom_id);
			$php_query->elements = [];
			foreach($arg1->elements as $node)
				$php_query->elements[] = $php_query->document->importNode($node, true);
			return $php_query;
		} elseif($arg1 instanceof DOMNode || (is_array($arg1) && isset($arg1[0]) && $arg1[0] instanceof DOMNode)) {
			/*
			 * Wrap DOM nodes with phpQuery object, import into document when needed:
			 */
			$php_query = new phpQueryObject($dom_id);
			if(!($arg1 instanceof DOMNodeList) && !is_array($arg1))
				$arg1 = [$arg1];
			$php_query->elements = [];
			foreach($arg1 as $node) {
				$same_document = $node->ownerDocument instanceof DOMDocument && !$node->ownerDocument->isSameNode($php_query->document);
				$php_query->elements[] = $same_document ? $php_query->document->importNode($node, true) : $node;
			}
			return $php_query;
		} elseif(self::isMarkup($arg1)) {
			/**
			 * Import HTML:
			 * pq('<div/>')
			 */
			$php_query = new phpQueryObject($dom_id);
			return $php_query->newInstance($php_query->documentWrapper->import($arg1));
		} else {
			/**
			 * Run CSS query:
			 * pq('div.myClass')
			 */
			$php_query = new phpQueryObject($dom_id);
			//			if($context && ($context instanceof PHPQUERY || is_subclass_of($context, 'phpQueryObject')))
			if($context instanceof phpQueryObject) {
				$php_query->elements = $context->elements;
			} elseif($context instanceof DOMNodeList) {
				$php_query->elements = [];
				foreach($context as $node)
					$php_query->elements[] = $node;
			} elseif($context instanceof DOMNode) {
				$php_query->elements = [$context];
			}
			return $php_query->find($arg1);
		}
	}







}