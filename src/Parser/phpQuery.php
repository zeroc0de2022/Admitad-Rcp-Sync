<?php
declare(strict_types = 1);


namespace Cpsync\Parser;

use Cpsync\Parser\phpQuery\Callback;
use Cpsync\Parser\phpQuery\CallbackParam;
use Cpsync\Parser\phpQuery\CallbackParamToRef;
use Cpsync\Parser\phpQuery\DOMDocWrapper;
use Cpsync\Parser\phpQuery\phpQueryPlugins;
use DOMDocument;
use DOMNodeList;
use Exception;
use Iterator;
use Cpsync\Mapper\Trait\Debug;
use Cpsync\Parser\phpQuery\phpQueryParts\Parts;

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

/**
 * phpQuery is a server-side, chainable, CSS3 selector driven
 * Document Object Model (DOM) API based on jQuery JavaScript Library.
 *
 * @version 0.9.5
 * @link http://code.google.com/p/phpquery/
 * @link http://phpquery-library.blogspot.com/
 * @link http://jquery.com/
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 * @package phpQuery
 */

// class names for instanceof
// TODO move them as class constants into phpQuery
define('DOMDOCUMENT', 'DOMDocument');
define('DOMELEMENT', 'DOMElement');
define('DOMNODELIST', 'DOMNodeList');
define('DOMNODE', 'DOMNode');


/**
 * phpQuery class.
 *
 * @package phpQuery
 * @property-read string $html
 * @property-read string $text
 * @property-read string $outerHTML
 * @property-read string $outerText
 * @property-read string $innerHtml
 * @property-read string $innerText
 * @property-read string $attr
 * @property-read string $prop
 * @property-read string $val
 * @property-read string $data
 * @property-read string $css
 * @property-read string $width
 * @property-read string $height
 * @property-read string $offset
 * @property-read string $offsetParent
 * @property-read string $position
 * @property-read string $scrollLeft
 * @property-read string $scrollTop
 * @property-read string $clientLeft
 * @property-read string $clientTop
 * @property-read string $clientWidth
 * @property-read string $clientHeight
 * @property-read string $scrollHeight
 * @property-read string $scrollWidth
 */
abstract class phpQuery
{
	use Debug;
	use Parts;

	public static bool $mbstringSupport = true;
	public static bool $debug = false;
	public static array $documents = [];
	public static string|null $defaultDocumentID = null;
	public static string $defaultDoctype = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
"http://www.w3.org/TR/html4/loose.dtd">';
	public static string $defaultCharset = 'UTF-8';
	public static phpQueryPlugins|array $plugins = [];
	public static array $pluginsLoaded = [];
	public static array $pluginsMethods = [];
	public static array $pluginsStaticMethods = [];
	public static array $extendMethods = [];
	public static array $extendStaticMethods = [];
	public static array $ajaxAllowedHosts = ['.'];
	public static array $ajaxSettings = ['url' => '',//TODO
		'global' => true, 'type' => 'GET', 'timeout' => null, 'contentType' => 'application/x-www-form-urlencoded', 'processData' => true, //		'async' => true,
		'data' => null, 'username' => null, 'password' => null, 'accepts' => ['xml' => 'application/xml, text/xml', 'html' => 'text/html', 'script' => 'text/javascript, application/javascript', 'json' => 'application/json, text/javascript', 'text' => 'text/plain', '_default' => '*/*']];
	public static string $lastModified;
	public static int $active = 0;
	public static int $dumpCount = 0;


	/**
	 * @throws \Exception
	 */
	public static function createDocWrapper($html, $content_type = null, $document_id = null)
	{
		if(function_exists('domxml_open_mem'))
			throw new Exception(__LINE__.': '.__METHOD__.' -> Old PHP4 DOM XML extension detected. phpQuery wont work until this extension is enabled.');
		$wrapper = null;
		if($html instanceof DOMDocument) {
			if(!self::getDocumentID($html)) {
				// new document, add it to phpQuery::$documents
				$wrapper = new DOMDocWrapper($html, $content_type, $document_id);
			}
		} else {
			$wrapper = new DOMDocWrapper($html, $content_type, $document_id);
		}
		//		$wrapper->docid = $docid;
		// bind document
		phpQuery::$documents[$wrapper->docid] = $wrapper;
		// remember last loaded document
		self::selectDocument($wrapper->docid);
		return $wrapper->docid;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public static function extend($target, $source): bool
	{
		switch($target) {
			case 'phpQueryObject':
				$target_ref = &self::$extendMethods;
				$target_ref2 = &self::$pluginsMethods;
				break;
			case 'phpQuery':
				$target_ref = &self::$extendStaticMethods;
				$target_ref2 = &self::$pluginsStaticMethods;
				break;
			default:
				throw new Exception(__LINE__.': '.__METHOD__.' -> Unsupported $target type');
		}
		if(is_string($source))
			$source = [$source => $source];
		foreach($source as $method => $callback) {
			if(isset($target_ref[$method])) {
				//				throw new Exception
				self::debug('Duplicate method '.$method.', cant extend '.$target);
				continue;
			}
			if(isset($target_ref2[$method])) {
				//				throw new Exception
				self::debug('Duplicate method `'.$method.'` from plugin '.$target_ref2[$method].', cant extend '.$target);
				continue;
			}
			$target_ref[$method] = $callback;
		}
		return true;
	}

	/**
	 * @throws Exception
	 */
	public static function plugin($class, $file = null): bool
	{
		//		if(str_starts_with($class, 'phpQuery'))
		//			$class = substr($class, 8);
		if(in_array($class, self::$pluginsLoaded))
			return true;
		if(!$file) {
			$file = $class.'.php';
		}
		$obj_class_exists = class_exists('phpQueryObjectPlugin_'.$class);
		$stat_class_exists = class_exists('phpQueryPlugin_'.$class);
		if(!$obj_class_exists && !$stat_class_exists) {
			require_once($file);
		}
		self::$pluginsLoaded[] = $class;
		// static methods
		if(class_exists('phpQueryPlugin_'.$class)) {
			$real_class = 'phpQueryPlugin_'.$class;
			$vars = get_class_vars($real_class);
			$loop = $vars['phpQueryMethods'] ?? get_class_methods($real_class);
			foreach($loop as $method) {
				if($method == '__initialize') {
					continue;
				}

				if(!is_callable([$real_class, $method])) {
					continue;
				}

				if(isset(self::$pluginsStaticMethods[$method])) {
					throw new Exception(__LINE__.': '.__METHOD__.' -> Duplicate method `'.$method.'` from plugin `'.$real_class.'` conflicts with same method from plugin '.self::$pluginsStaticMethods[$method]);
				}
				self::$pluginsStaticMethods[$method] = $class;
			}
			if(method_exists($real_class, '__initialize')) {
				call_user_func_array([$real_class, '__initialize'], []);
			}
		}
		// object methods
		if(class_exists('phpQueryObjectPlugin_'.$class)) {
			$real_class = 'phpQueryObjectPlugin_'.$class;
			$vars = get_class_vars($real_class);
			$loop = $vars['phpQueryMethods'] ?? get_class_methods($real_class);
			foreach($loop as $method) {
				if(!is_callable([$real_class, $method])) {
					continue;
				}

				if(isset(self::$pluginsMethods[$method])) {
					throw new Exception(__LINE__.': '.__METHOD__.' -> Duplicate method `'.$method.'` from plugin `'.$real_class.'` conflicts with same method from plugin '.self::$pluginsMethods[$method]);
				}
				self::$pluginsMethods[$method] = $class;
			}
		}
		return true;
	}


	/**
	 * @param $data
	 * @return string
	 */
	public static function param($data): string
	{
		return http_build_query($data, '', '&');
	}

	/**
	 *  Dom Node list to array
	 * @param $DOMNodeList
	 * @return array
	 */
	public static function domNodeListToArray($DOMNodeList): array
	{
		$array = [];
		if(!$DOMNodeList)
			return $array;
		foreach($DOMNodeList as $node)
			$array[] = $node;
		return $array;
	}

	/**
	 * @param $text
	 * @return void
	 */
	public static function debug($text): void
	{
		if(self::$debug)
			var_dump($text);
	}


	/**
	 * @param $data
	 * @param $type
	 * @param $options
	 * @return bool|mixed
	 */
	public static function httpData($data, $type, $options): mixed
	{
		if(isset($options['dataFilter']) && $options['dataFilter'])
			$data = self::callbackRun($options['dataFilter'], [$data, $type]);
		if(is_string($data)) {
			if($type == 'json') {
				if(isset($options['_jsonp']) && $options['_jsonp']) {
					$data = preg_replace('/^\s*\w+\((.*)\)\s*$/s', '$1', $data);
				}
				$data = self::parseJSON($data);
			}
		}
		return $data;
	}



	/**
	 * @throws \Exception
	 */


	/* @noinspection PhpUnused */
	public static function makeArray($object): array
	{
		$array = [];
		if($object instanceof DOMNodeList) {
			foreach($object as $value)
				$array[] = $value;
		} elseif(is_object($object) && !($object instanceof Iterator)) {
			foreach(get_object_vars($object) as $name => $value)
				$array[0][$name] = $value;
		} else {
			foreach($object as $name => $value)
				$array[0][$name] = $value;
		}
		return $array;
	}

	/**
	 * @param $object
	 * @param $callback
	 * @param $param1
	 * @param $param2
	 * @param $param3
	 * @return void
	 */
	public static function each($object, $callback, $param1 = null, $param2 = null, $param3 = null): void
	{
		$param_structure = null;
		if(func_num_args() > 2) {
			$param_structure = func_get_args();
			$param_structure = array_slice($param_structure, 2);
		}
		if(is_object($object) && !($object instanceof Iterator)) {
			foreach(get_object_vars($object) as $name => $value)
				phpQuery::callbackRun($callback, [$name, $value], $param_structure);
		} else {
			foreach($object as $name => $value)
				phpQuery::callbackRun($callback, [$name, $value], $param_structure);
		}
	}

	/**
	 * @param $array
	 * @param $callback
	 * @param $param1
	 * @param $param2
	 * @param $param3
	 * @return array
	 */
	public static function map($array, $callback, $param1 = null, $param2 = null, $param3 = null): array
	{
		$result = [];
		$param_structure = null;
		if(func_num_args() > 2) {
			$param_structure = func_get_args();
			$param_structure = array_slice($param_structure, 2);
		}
		foreach($array as $val) {
			$vv = phpQuery::callbackRun($callback, [$val], $param_structure);
			//			$callbackArgs = $args;
			//			foreach($args as $iey => $arg) {
			//				$callbackArgs[$iey] = $arg instanceof CallbackParam
			//					? $vey
			//					: $arg;
			//			}
			//			$vv = call_user_func_array($callback, $callbackArgs);
			if(is_array($vv)) {
				foreach($vv as $vvv)
					$result[] = $vvv;
			} elseif($vv !== null) {
				$result[] = $vv;
			}
		}
		return $result;
	}

	/**
	 * @param $callback
	 * @param array $params
	 * @param $param_structure
	 * @return bool|mixed
	 */
	public static function callbackRun($callback, array $params = [], $param_structure = null): mixed
	{
		if(!$callback)
			return false;
		if($callback instanceof CallbackParamToRef) {
			// TODO support ParamStructure to select which $param push to reference
			if(isset($params[0]))
				$callback->callback = $params[0];
			return true;
		}
		$is_instance = function($selector, $const) {
			return $selector instanceof $const;
		};
		if($is_instance($callback, Callback::class)) {
			$param_structure = $callback->params;
			$callback = $callback->callback;
		}
		if(!$param_structure)
			return call_user_func_array($callback, $params);
		$num = 0;
		foreach($param_structure as $key => $val) {
			$param_structure[$key] = $val instanceof CallbackParam ? $params[$num++] : $val;
		}
		return call_user_func_array($callback, $param_structure);
	}

	/**
	 * @param $one
	 * @param $two
	 * @return mixed
	 */
	public static function merge($one, $two): mixed
	{
		$elements = $one->elements;
		foreach($two->elements as $node) {
			$exists = false;
			foreach($elements as $node2) {
				if($node2->isSameNode($node))
					$exists = true;
			}
			if(!$exists)
				$elements[] = $node;
		}
		return $elements;
	}

	/* @noinspection PhpUnused */
	/**
	 * @param $array
	 * @param $callback
	 * @param mixed $invert
	 * @return array
	 */
	public static function grep($array, $callback, mixed $invert = false): array
	{
		$result = [];
		foreach($array as $key => $val) {
			$rev = call_user_func_array($callback, [$val, $key]);
			if($rev === !$invert)
				$result[] = $val;
		}
		return $result;
	}


	/**
	 * @param $code
	 * @return string
	 */
	public static function php($code): string
	{
		return self::code('php', $code);
	}

	/**
	 * @param $type
	 * @param $code
	 * @return string
	 */
	public static function code($type, $code): string
	{
		return "<$type><!-- ".trim($code)." --></$type>";
	}

	/**
	 * @param $method
	 * @param $params
	 * @return mixed
	 */
	public static function __callStatic($method, $params)
	{
		return call_user_func_array([phpQuery::$plugins, $method], $params);
	}

}

/**
 * @param $arg1
 * @param $context
 * @return mixed
 */
function pq($arg1, $context = null): mixed
{
	$args = func_get_args();
	//dd(__METHOD__, __NAMESPACE__, __FILE__, __LINE__);
	return call_user_func_array(['Cpsync\Parser\phpQuery', 'pq'], $args);
}

// add plugins dir and Zend framework to include path
set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/phpQuery/'.PATH_SEPARATOR.dirname(__FILE__).'/phpQuery/plugins/');
// why ? no __call nor __get for statics in php...
// XXX __callStatic will be available in PHP 5.3
phpQuery::$plugins = new phpQueryPlugins();
