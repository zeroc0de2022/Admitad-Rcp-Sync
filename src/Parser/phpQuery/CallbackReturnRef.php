<?php
/*
Date: 14.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);

namespace Cpsync\Parser\phpQuery;

/* @noinspection PhpUnused */

class CallbackReturnRef extends Callback implements ICallbackNamed
{
	protected mixed $reference;

	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct(&$reference)
	{
		$this->reference =& $reference;
		$this->callback = [$this, 'callback'];
	}

	public function callback()
	{
		return $this->reference;
	}

	public function getName(): string
	{
		return 'Callback: '.$this->name;
	}

	public function hasName(): bool
	{
		return isset($this->name) && $this->name;
	}
}