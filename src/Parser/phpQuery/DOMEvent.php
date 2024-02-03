<?php
/*
Date: 14.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);

namespace Cpsync\Parser\phpQuery;

use DOMNode;

/**
 * DOMEvent class.
 *
 * Based on
 * @link http://developer.mozilla.org/En/DOM:event
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 * @todo implement ArrayAccess ?
 */
class DOMEvent
{
	public bool $bubbles = true;
	public DOMNode $currentTarget;
	public DOMEvent $relatedTarget;
	public DOMEvent $target;
	public int|null $timeStamp = null;
	public string|null $type = null;
	public bool $runDefault = true;
	public mixed $data;

	public function __construct($data)
	{
		foreach($data as $key => $val) {
			$this->$key = $val;
		}
		if(!$this->timeStamp) {
			$this->timeStamp = time();
		}
	}

	/**
	 * Prevents the default action of the event.
	 * @noinspection PhpUnused
	 * @return void
	 */
	public function preventDefault(): void
	{
		$this->runDefault = false;
	}

	/**
	 * Stops the propagation of events further along in the DOM tree.
	 * @return void
	 * @noinspection PhpUnused
	 */
	public function stopPropagation(): void
	{
		$this->bubbles = false;
	}
}