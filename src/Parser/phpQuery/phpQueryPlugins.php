<?php
/*
Date: 14.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);


namespace Cpsync\Parser\phpQuery;


use Cpsync\Parser\phpQuery;
use Exception;
use Cpsync\Mapper\Trait\Debug;
use Cpsync\Parser\phpQuery\phpQueryParts\Parts;

class phpQueryPlugins
{
	use Debug;
	use Parts;

	/**
	 * @throws \Exception
	 */
	public function __call($method, $args)
	{
		if(isset(phpQuery::$extendStaticMethods[$method])) {
			$return = call_user_func_array(phpQuery::$extendStaticMethods[$method], $args);
			return $return ?? $this;
		} elseif(isset(phpQuery::$pluginsStaticMethods[$method])) {
			$class = phpQuery::$pluginsStaticMethods[$method];
			$real_class = "phpQueryPlugin_$class";
			$return = call_user_func_array([$real_class, $method], $args);
			return $return ?? $this;
		}
		//$this->debugStringBacktrace();
		throw new Exception(__LINE__.': '.__METHOD__." -> Method '$method' doesnt exist");
	}
}