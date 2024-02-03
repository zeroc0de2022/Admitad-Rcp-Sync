<?php
/*
Date: 14.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);

namespace Cpsync\Parser\phpQuery;


/* @noinspection PhpUnused */

class CallbackReturnVal extends Callback implements ICallbackNamed
{
	/* @noinspection PhpUnused */
	protected CallbackReturnVal $value;
	protected mixed $name;

	/** @noinspection PhpMissingParentConstructorInspection */
	public function __construct($value, $name = null)
	{
		$this->value =& $value;
		$this->name = $name;
		$this->callback = [$this, 'callback'];
	}

	public function callback(): CallbackReturnVal
	{
		return $this->value;
	}

	public function __toString(): string
	{
		return $this->getName();
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