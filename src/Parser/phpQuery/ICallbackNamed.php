<?php
/*
Date: 14.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);

namespace Cpsync\Parser\phpQuery;


interface ICallbackNamed
{
	public function hasName();

	public function getName();
}