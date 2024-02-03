<?php
/*
Date: 14.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);


namespace Cpsync\Parser\phpQuery;


use Cpsync\Parser\phpQuery;
use Exception;
use Cpsync\Parser\phpQuery\phpQueryParts\Parts;

abstract class phpQueryEvents
{
	use Parts;

	/**
	 * @throws \Exception
	 */
	public static function trigger($document, $type, $data = [], $node = null): void
	{
		// trigger: function(type, data, elem, donative, extra) {
		$document_id = self::getDocumentID($document);
		$namespace = null;
		if(str_contains($type, '.')) {
			[$name, $namespace] = explode('.', $type);
		} else {
			$name = $type;
		}
		if(!$node) {
			if(self::issetGlobal($document_id, $type)) {
				$pq = self::getDocument($document_id);
				// TODO check add($pq->document)
				$pq->find('*')->add($pq->document)->trigger($type, $data);
			}
		} else {
			if(isset($data[0]) && $data[0] instanceof DOMEvent) {
				$event = $data[0];
				$event->relatedTarget = $event->target;
				$event->target = $node;
				$data = array_slice($data, 1);
			} else {
				$event = new DOMEvent(['type' => $type, 'target' => $node, 'timeStamp' => time()]);
			}
			$num = 0;
			while($node) {
				// TODO whois
				phpQuery::debug('Triggering '.($num ? 'bubbled ' : '')."event '$type' on node \n");//.phpQueryObject::whois($node).'\n');
				$event->currentTarget = $node;
				$event_node = self::getNode($document_id, $node);
				if(isset($event_node->eventHandlers)) {
					foreach($event_node->eventHandlers as $event_type => $handlers) {
						$event_namespace = null;
						if(str_contains($type, '.')) {
							[$event_name, $event_namespace] = explode('.', $event_type);
						} else {
							$event_name = $event_type;
						}
						if($name != $event_name)
							continue;
						if($namespace && $event_namespace && $namespace != $event_namespace)
							continue;
						foreach($handlers as $handler) {
							phpQuery::debug('Calling event handler\n');
							$event->data = $handler['data'] ?? null;
							$params = array_merge([$event], $data);
							$return = phpQuery::callbackRun($handler['callback'], $params);
							if($return === false) {
								$event->bubbles = false;
							}
						}
					}
				}
				// to bubble or not to bubble...
				if(!$event->bubbles)
					break;
				$node = $node->parentNode;
				$num++;
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	public static function add($document, $node, $type, $data, $callback = null): void
	{
		phpQuery::debug("Binding '$type' event");
		$document_id = self::getDocumentID($document);

		$event_node = self::getNode($document_id, $node);
		if(!$event_node)
			$event_node = self::setNode($document_id, $node);
		if(!isset($event_node->eventHandlers[$type])) {
			$event_node->eventHandlers[$type] = [];
		}

		$event_node->eventHandlers[$type][] = ['callback' => $callback, 'data' => $data,];
	}

	/**
	 * @throws \Exception
	 */
	public static function remove($document, $node, $type = null, $callback = null): void
	{
		$document_id = self::getDocumentID($document);
		$event_node = self::getNode($document_id, $node);
		if(!is_object($event_node)) {
			throw new Exception(__LINE__.': '.__METHOD__.' -> Event node for node not found');
		}
		if(isset($event_node->eventHandlers[$type])) {
			if($callback) {
				foreach($event_node->eventHandlers[$type] as $key => $handler)
					if($handler['callback'] == $callback)
						unset($event_node->eventHandlers[$type][$key]);
			} else {
				unset($event_node->eventHandlers[$type]);
			}
		}
	}

	/**
	 * @throws \Exception
	 */
	protected static function getNode($document_id, $node): string
	{
		foreach(phpQuery::$documents[$document_id]->eventsNodes as $event_node) {
			if($node->isSameNode($event_node)) {
				return $event_node;
			}
		}
		throw new Exception(__LINE__.': '.__METHOD__.' -> Event node for node not found');
	}

	protected static function setNode($document_id, $node)
	{
		phpQuery::$documents[$document_id]->eventsNodes[] = $node;
		return phpQuery::$documents[$document_id]->eventsNodes[count(phpQuery::$documents[$document_id]->eventsNodes) - 1];
	}

	protected static function issetGlobal($document_id, $type): bool
	{
		return isset(phpQuery::$documents[$document_id]) && in_array($type, phpQuery::$documents[$document_id]->eventsGlobal);
	}
}