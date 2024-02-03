<?php
declare(strict_types = 1);
/*
Date: 28.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Mapper\Trait;

use JetBrains\PhpStorm\NoReturn;

/**
 * Trait Debug
 * @package Cpsync\Mapper\Trait
 */
trait Debug
{


	/**
	 * @param bool $exit
	 * @return true|void
	 */
	#[NoReturn] public function debugStringBacktrace(bool $exit = true)
	{
		ob_start();
		debug_print_backtrace();
		$trace = ob_get_contents();
		ob_end_clean();
		// Remove first item from backtrace as it's this function which
		// is redundant.
		$trace = preg_replace ('/^#0\s+'.__FUNCTION__."[^\n]*\n/", '', $trace, 1);
		// Renumber backtrace items.
		$trace = preg_replace ('/^#(\d+)/mi', '\'#\'.($1 - 1)', $trace);
		echo '<pre>';
		print_r($trace);
		echo '</pre>';
		if($exit) {
			die($trace);
		}
		return true;
	}

}