<?php
/*
Date: 14.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);

namespace Cpsync\Parser\phpQuery;

use ArrayAccess;
use Closure;
use Countable;
use Cpsync\Parser\phpQuery;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use Exception;
use Iterator;
use function Cpsync\Parser\pq;
use Cpsync\Parser\phpQuery\phpQueryParts\Parts;

/**
 * Class representing phpQuery objects.
 *
 * @author Tobiasz Cudnik <tobiasz.cudnik/gmail.com>
 * @package phpQuery
 * @method phpQueryObject clone () clone ()
 * @method phpQueryObject empty() empty()
 * @property Int $length
 */
class phpQueryObject implements Iterator, Countable, ArrayAccess
{
	use Parts;

	/* @noinspection PhpUnused */
	public function toReference(&$var): phpQueryObject
	{
		return $var = $this;
	}

	private int $length;
	/* @noinspection PhpUnused */
	/**
	 * @throws \Exception
	 */
	public function documentFragment($state = null): phpQueryObject|bool
	{
		if($state) {
			phpQuery::$documents[$this->getDocumentID()]['documentFragment'] = $state;
			return $this;
		}
		return $this->documentFragment;
	}

	public string $documentID;
	public DOMDocument|DOMNode|null $document = null;
	public string $charset;
	public DOMDocWrapper $documentWrapper;
	public DOMXPath $xpath;
	public array $elements = [];
	protected array $elementsBackup = [];
	protected phpQueryObject $previous;
	protected mixed $root = null;
	public bool $documentFragment = true;
	protected array $elementsInterator = [];
	protected bool $valid = false;
	protected int $current;
	/**
	 * @var mixed
	 */
	private mixed $loadSelector;

	/**
	 * Create new phpQuery object.
	 * @throws Exception
	 */
	public function __construct($documentID)
	{
		//		if($documentID instanceof self)
		//			var_dump($documentID->getDocumentID());
		$id = $documentID instanceof self ? $documentID->getDocumentID() : $documentID;
		//		var_dump($id);
		if(!isset(phpQuery::$documents[$id])) {
			//			var_dump(phpQuery::$documents);
			throw new Exception(__LINE__.': '.__METHOD__.' -> Document with ID '.$id.' is not loaded. Use phpQuery::newDocument($html) or phpQuery::newDocumentFile($file) first.');
		}
		$this->documentID = $id;
		$this->documentWrapper =& phpQuery::$documents[$id];
		$this->document =& $this->documentWrapper->document;
		$this->xpath =& $this->documentWrapper->xpath;
		$this->charset =& $this->documentWrapper->charset;
		$this->documentFragment =& $this->documentWrapper->isDocumentFragment;
		// TODO check $this->DOM->documentElement;
		//		$this->root = $this->document->documentElement;
		$this->root =& $this->documentWrapper->root;
		//		$this->toRoot();
		$this->elements = [$this->root];
	}

	public function __get($attr)
	{
		return match ($attr) {
			'length' => $this->size(),
			default => $this->$attr,
		};
	}

	protected function isRoot($node): bool
	{
		//		return $node instanceof DOMDOCUMENT || $node->tagName == 'html';
		return $node instanceof DOMDocument || ($node instanceof DOMElement && $node->tagName == 'html') || $this->root->isSameNode($node);
	}

	protected function stackIsRoot(): bool
	{
		return $this->size() == 1 && $this->isRoot($this->elements[0]);
	}

	public function toRoot(): phpQueryObject
	{
		$this->elements = [$this->root];
		return $this;
		//		return $this->newInstance(array($this->root));
	}
	/* @noinspection PhpUnused */
	/**
	 * @throws \Exception
	 */
	public function getDocumentIDRef(&$document_id): phpQueryObject
	{
		$document_id = $this->getDocumentID();
		return $this;
	}


	/* @noinspection PhpUnused */
	public function getDOMDocument(): DOMNode|DOMDocument|null
	{
		return $this->document;
	}

	public function getDocumentID()
	{
		return $this->documentID;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function unloadDocument(): void
	{
		self::unloadDocuments($this->getDocumentID());
	}

	public function isHTML(): bool
	{
		return $this->documentWrapper->isHTML;
	}

	public function isXHTML(): bool
	{
		return $this->documentWrapper->isXHTML;
	}

	public function isXML(): bool
	{
		return $this->documentWrapper->isXML;
	}

	/**
	 * @throws \Exception
	 */
	public function serialize(): string
	{
		return phpQuery::param($this->serializeArray());
	}

	/**
	 * @throws \Exception
	 */
	public function serializeArray($submit = null): array
	{
		$source = $this->filter('form, input, select, textarea')->find('input, select, textarea')->andSelf()->not('form');
		$return = [];
		//		$source->dumpDie();
		foreach($source as $input) {
			$input = self::pq($input);
			if($input->is('[disabled]'))
				continue;
			if(!$input->is('[name]'))
				continue;
			if($input->is('[type=checkbox]') && !$input->is('[checked]'))
				continue;
			// jquery diff
			if($submit && $input->is('[type=submit]')) {
				if($submit instanceof DOMElement && !$input->elements[0]->isSameNode($submit)) {
					continue;
				} elseif(is_string($submit) && $input->attr('name') != $submit) {
					continue;
				}
			}
			$return[] = ['name' => $input->attr('name'), 'value' => $input->val(),];
		}
		return $return;
	}

	protected function debug($in): void
	{
		if(!phpQuery::$debug)
			return;
		print('<pre>');
		print_r($in);
		// file debug
		//		file_put_contents(dirname(__FILE__).'/phpQuery.log', print_r($in, true).'\n', FILE_APPEND);
		// quite handy debug trace
		//		if(is_array($in))
		//			print_r(array_slice(debug_backtrace(), 3));
		print("</pre>\n");
	}

	protected function isRegexp($pattern): bool
	{
		return in_array($pattern[mb_strlen($pattern) - 1], ['^', '*', '$']);
	}

	protected function isChar($char): bool|int
	{
		return extension_loaded('mbstring') && phpQuery::$mbstringSupport ? mb_eregi('\w', $char) : preg_match('@\w@', $char);
	}

	protected function parseSelector($query): array
	{
		// clean spaces
		// TODO include this inside parsing ?
		$query = trim(preg_replace('@\s+@', ' ', preg_replace('@\s*([>+~])\s*@', '\\1', $query)));
		$queries = [[]];
		if(!$query) {
			return $queries;
		}
		$clone_obj = $this;
		$is_num_chairs = function($query_num, $class_chars, $space_allowed) use ($clone_obj): bool {
			return isset($query_num) && ($clone_obj->isChar($query_num) || in_array($query_num, $class_chars) || $query_num == '*' || ($query_num == ' ' && $space_allowed));
		};
		function for_stack(&$tmp, int &$stack, $query, int $num, string $compare): void
		{
			while(isset($query[++$num])) {
				$tmp .= $query[$num];
				if($query[$num] == $compare[0]) {
					$stack++;
				} elseif($query[$num] == $compare[1]) {
					$stack--;
					if(!$stack) {
						break;
					}
				}
			}
		}

		$return =& $queries[0];
		$special_chars = ['>', ' '];
		$strlen = mb_strlen($query);
		$class_chars = ['.', '-'];
		$pseudo_chars = ['-'];
		$tag_chars = ['*', '|', '-'];
		// split multibyte string
		$query_ = [];
		for($num = 0; $num < $strlen; $num++)
			$query_[] = mb_substr($query, $num, 1);
		$query = $query_;
		$num = 0;
		while($num < $strlen) {
			$cum = $query[$num];
			$tmp = '';
			// TAG
			if($this->isChar($cum) || in_array($cum, $tag_chars)) {
				while(isset($query[$num]) && ($this->isChar($query[$num]) || in_array($query[$num], $tag_chars))) {
					$tmp .= $query[$num];
					$num++;
				}
				$return[] = $tmp;
				// IDs
			} elseif($cum == '#') {
				$num++;
				while(isset($query[$num]) && ($this->isChar($query[$num]) || $query[$num] == '-')) {
					$tmp .= $query[$num];
					$num++;
				}
				$return[] = '#'.$tmp;
				// SPECIAL CHARS
			} elseif(in_array($cum, $special_chars)) {
				$return[] = $cum;
				$num++;
			} elseif($cum == ',') {
				$queries[] = [];
				$return =& $queries[count($queries) - 1];
				$num++;
				while(isset($query[$num]) && $query[$num] == ' ')
					$num++;
			} elseif($cum == '.') {
				while(isset($query[$num]) && ($this->isChar($query[$num]) || in_array($query[$num], $class_chars))) {
					$tmp .= $query[$num];
					$num++;
				}
				$return[] = $tmp;
				// ~ General Sibling Selector
			} elseif($cum == '+' || $cum == '~') {
				$space_allowed = true;
				$tmp .= $query[$num++];
				while($is_num_chairs($query[$num], $class_chars, $space_allowed)) {
					if($query[$num] != ' ')
						$space_allowed = false;
					$tmp .= $query[$num];
					$num++;
				}
				$return[] = $tmp;
				// ATTRS
			} elseif($cum == '[') {
				$stack = 1;
				$tmp .= $cum;
				for_stack($tmp, $stack, $query, $num, '[]');
				$return[] = $tmp;
				$num++;
				// PSEUDO CLASSES
			} elseif($cum == ':') {
				$stack = 1;
				$tmp .= $query[$num++];
				while(isset($query[$num]) && ($this->isChar($query[$num]) || in_array($query[$num], $pseudo_chars))) {
					$tmp .= $query[$num];
					$num++;
				}
				// with arguments ?
				if(isset($query[$num]) && $query[$num] == '(') {
					$tmp .= $query[$num];
					for_stack($tmp, $stack, $query, $num, '()');
					$return[] = $tmp;
					$num++;
				} else {
					$return[] = $tmp;
				}
			} else {
				$num++;
			}
		}
		foreach($queries as $key => $val) {
			if(isset($val[0])) {
				if(isset($val[0][0]) && $val[0][0] == ':')
					array_unshift($queries[$key], '*');
				if($val[0] != '>')
					array_unshift($queries[$key], ' ');
			}
		}
		return $queries;
	}

	public function get($url = null)
	{
		$return = isset($url) ? ($this->elements[$url] ?? null) : $this->elements;
		// pass thou callbacks
		$args = func_get_args();
		$args = array_slice($args, 1);
		foreach($args as $callback) {
			if(is_array($return)) {
				foreach($return as $key => $val)
					$return[$key] = phpQuery::callbackRun($callback, [$val]);
			} else {
				$return = phpQuery::callbackRun($callback, [$return]);
			}
		}
		return $return;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function getString($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
	{
		if($index) {
			$return = $this->eq($index)->text();
		} else {
			$return = [];
			for($num = 0; $num < $this->size(); $num++) {
				$return[] = $this->eq($num)->text();
			}
		}
		// pass thou callbacks
		$args = func_get_args();
		$args = array_slice($args, 1);
		foreach($args as $callback) {
			$return = phpQuery::callbackRun($callback, [$return]);
		}
		return $return;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function getStrings($index = null, $callback1 = null, $callback2 = null, $callback3 = null)
	{
		$args = [];
		if($index) {
			$return = $this->eq($index)->text();
		} else {
			$return = [];
			for($num = 0; $num < $this->size(); $num++) {
				$return[] = $this->eq($num)->text();
			}
			// pass thou callbacks
			$args = func_get_args();
			$args = array_slice($args, 1);
		}
		foreach($args as $callback) {
			if(is_array($return))
				foreach($return as $key => $val) {
					$return[$key] = phpQuery::callbackRun($callback, [$val]);
				} else {
				$return = phpQuery::callbackRun($callback, [$return]);
			}
		}
		return $return;
	}

	/**
	 * @throws \Exception
	 */
	public function newInstance($new_stack = null)
	{
		$class = get_class($this);
		// support inheritance by passing old object to overloaded constructor
		$new = $class != 'phpQuery' ? new $class($this, $this->getDocumentID()) : new phpQueryObject($this->getDocumentID());
		$new->previous = $this;
		if(is_null($new_stack)) {
			$new->elements = $this->elements;
			if($this->elementsBackup)
				$this->elements = $this->elementsBackup;
		} elseif(is_string($new_stack)) {
			$new->elements = self::pq($new_stack, $this->getDocumentID())->stack();
		} else {
			$new->elements = $new_stack;
		}
		return $new;
	}

	protected function matchClasses($class, $node): bool
	{
		// multi-class
		if(mb_strpos($class, '.', 1)) {
			$classes = explode('.', substr($class, 1));
			$classes_count = count($classes);
			$node_classes = explode(' ', $node->getAttribute('class'));
			$node_classes_count = count($node_classes);
			if($classes_count > $node_classes_count)
				return false;
			$diff = count(array_diff($classes, $node_classes));
			if(!$diff)
				return true;
			// single-class
		} else {
			return in_array(// strip leading dot from class name
				substr($class, 1), // get classes for element as array
				explode(' ', $node->getAttribute('class')));
		}
		return false;
	}

	/**
	 * @throws \Exception
	 */
	protected function runQuery($xquery, $selector = null, $compare = null): void
	{
		if($compare && !method_exists($this, $compare))
			throw new Exception(__LINE__.': '.__METHOD__." -> Method '$compare' doesn't exist");
		$stack = [];
		if(!$this->elements)
			$this->debug('Stack empty, skipping...');
		//		var_dump($this->elements[0]->nodeType);
		// element, document
		foreach($this->stack([1, 9, 13]) as $key => $stack_node) {
			unset($key);
			$detach_after = false;
			// to work on detached nodes we need temporary place them somewhere
			$test_node = $stack_node;
			while($test_node) {
				if(!$test_node->parentNode && !$this->isRoot($test_node)) {
					$this->root->appendChild($test_node);
					$detach_after = $test_node;
					break;
				}
				$test_node = $test_node->parentNode ?? null;
			}
			// XXX tmp ?
			$xpath = $this->getNodeXpath($stack_node);
			// FIXME pseudoclasses-only query, support XML
			$query = $xquery == '//' && $xpath == '/html[1]' ? '//*' : $xpath.$xquery;
			$this->debug("XPATH: $query");
			// run query, get elements
			$nodes = $this->xpath->query($query);
			$this->debug('QUERY FETCHED');
			if(!$nodes->length)
				$this->debug('Nothing found');
			$debug = [];
			foreach($nodes as $node) {
				$matched = false;
				if($compare) {
					if(phpQuery::$debug) {
						$this->debug('Found: '.$this->whois($node).", comparing with $compare()");
					}
					// TODO ??? use phpQuery::callbackRun()
					if(call_user_func_array([$this, $compare], [$selector, $node])) {
						$matched = true;
					}
				} else {
					$matched = true;
				}
				if($matched) {
					if(phpQuery::$debug) {
						$debug[] = $this->whois($node);
					}
					$stack[] = $node;
				}
			}
			if(phpQuery::$debug) {
				$this->debug('Matched '.count($debug).': '.implode(', ', $debug));
			}
			if($detach_after)
				$this->root->removeChild($detach_after);
		}
		$this->elements = $stack;
	}

	/**
	 * @throws \Exception
	 */
	public function find($selectors, $context = null, $no_history = false): phpQueryObject
	{
		if(!$no_history)
			// backup last stack /for end()/
			$this->elementsBackup = $this->elements;
		// allow to define context
		// TODO combine code below with self::pq() context guessing code
		// as generic function
		if($context) {
			if(!is_array($context) && $context instanceof DOMElement) {
				$this->elements = [$context];
			} elseif(is_array($context)) {
				$this->elements = [];
				foreach($context as $cum)
					if($cum instanceof DOMElement)
						$this->elements[] = $cum;
			} elseif($context instanceof self) {
				$this->elements = $context->elements;
			}
		}
		$queries = $this->parseSelector($selectors);
		$this->debug(['FIND', $selectors, $queries]);
		$xquery = '';
		// remember stack state because of multi-queries
		$old_stack = $this->elements;
		// here we will be keeping found elements
		$stack = [];
		foreach($queries as $selector) {
			$this->elements = $old_stack;
			$delimiter_before = false;
			foreach($selector as $sel) {
				// TAG
				$pattern = ['@^[\w|\||-]+$@'];

				$is_tag = extension_loaded('mbstring') && phpQuery::$mbstringSupport ? mb_ereg_match('^[\w|\||-]+$', $sel) || $sel == '*' : preg_match(implode('', $pattern), $sel) || $sel == '*';
				if($is_tag) {
					if($this->isXML()) {
						// namespace support
						if(mb_strpos($sel, '|') !== false) {
							[$ns, $tag] = explode('|', $sel);
							$xquery .= "$ns:$tag";
						} elseif($sel == '*') {
							$xquery .= '*';
						} else {
							$xquery .= "*[local-name()='$sel']";
						}
					} else {
						$xquery .= $sel;
					}
					// ID
				} elseif($sel[0] == '#') {
					if($delimiter_before)
						$xquery .= '*';
					$xquery .= "[@id='".substr($sel, 1)."']";
					// ATTRIBUTES
				} elseif($sel[0] == '[') {
					if($delimiter_before)
						$xquery .= '*';
					// strip side brackets
					$attr = trim($sel, '][');
					$execute = false;
					// attr with specifed value
					if(mb_strpos($sel, '=')) {
						[$attr, $value] = explode('=', $attr);
						$value = trim($value, "'\"");
						if($this->isRegexp($attr)) {
							// cut regexp character
							$attr = substr($attr, 0, -1);
							$execute = true;
							$xquery .= "[@$attr]";
						} else {
							$xquery .= "[@$attr='$value']";
						}
						// attr without specified value
					} else {
						$xquery .= "[@$attr]";
					}
					if($execute) {
						$this->runQuery($xquery, $sel, 'is');
						$xquery = '';
						if(!$this->length())
							break;
					}
					// CLASSES
				} elseif($sel[0] == '.') {
					// TODO use return $this->find("./self::*[contains(concat(\" \",@class,\" \"), \" $class \")]");
					// thx wizDom ;)
					if($delimiter_before)
						$xquery .= '*';
					$xquery .= '[@class]';
					$this->runQuery($xquery, $sel, 'matchClasses');
					$xquery = '';
					if(!$this->length())
						break;
					// ~ General Sibling Selector
				} elseif($sel[0] == '~') {
					$this->runQuery($xquery);
					$xquery = '';
					$this->elements = $this->siblings(substr($sel, 1))->elements;
					if(!$this->length())
						break;
					// + Adjacent sibling selectors
				} elseif($sel[0] == '+') {
					// TODO /following-sibling::
					$this->runQuery($xquery);
					$xquery = '';
					$sub_selector = substr($sel, 1);
					$sub_elements = $this->elements;
					$this->elements = [];
					foreach($sub_elements as $node) {
						// search first DOMElement sibling
						$test = $node->nextSibling;
						while($test && !($test instanceof DOMElement))
							$test = $test->nextSibling;
						if($test && $this->is($sub_selector, $test))
							$this->elements[] = $test;
					}
					if(!$this->length())
						break;
					// PSEUDO CLASSES
				} elseif($sel[0] == ':') {
					// TODO optimization for :first :last
					if($xquery) {
						$this->runQuery($xquery);
						$xquery = '';
					}
					if(!$this->length())
						break;
					$this->pseudoClasses($sel);
					if(!$this->length())
						break;
					// DIRECT DESCENDANDS
				} elseif($sel == '>') {
					$xquery .= '/';
					$delimiter_before = 2;
					// ALL DESCENDANDS
				} elseif($sel == ' ') {
					$xquery .= '//';
					$delimiter_before = 2;
					// ERRORS
				} else {
					phpQuery::debug("Unrecognized token '$sel'");
				}
				$delimiter_before = $delimiter_before === 2;
			}
			// run query if any
			if($xquery && $xquery != '//') {
				$this->runQuery($xquery);
				$xquery = '';
			}
			foreach($this->elements as $node)
				if(!$this->elementsContainsNode($node, $stack))
					$stack[] = $node;
		}
		$this->elements = $stack;
		return $this->newInstance();
	}

	/**
	 * @throws \Exception
	 */
	protected function pseudoClasses($class): void
	{
		$args = 0;
		// TODO clean args parsing ?
		$class = ltrim($class, ':');
		$have_args = mb_strpos($class, '(');
		if($have_args !== false) {
			$args = substr($class, $have_args + 1, -1);
			$class = substr($class, 0, $have_args);
		}

		switch($class) {
			case 'even':
			case 'odd':
				$stack = [];
				foreach($this->elements as $num => $node) {
					if($class == 'even' && ($num % 2) == 0) {
						$stack[] = $node;
					} elseif($class == 'odd' && $num % 2) {
						$stack[] = $node;
					}
				}
				$this->elements = $stack;
				break;
			case 'eq':
				$key = intval($args);
				$this->elements = isset($this->elements[$key]) ? [$this->elements[$key]] : [];
				break;
			case 'gt':
				$this->elements = array_slice($this->elements, $args + 1);
				break;
			case 'lt':
				$this->elements = array_slice($this->elements, 0, $args + 1);
				break;
			case 'first':
				if(isset($this->elements[0]))
					$this->elements = [$this->elements[0]];
				break;
			case 'last':
				if($this->elements)
					$this->elements = [$this->elements[count($this->elements) - 1]];
				break;
			case 'contains':
				$text = trim($args, "\"'");
				$stack = [];
				foreach($this->elements as $node) {
					if(mb_stripos($node->textContent, $text) === false)
						continue;
					$stack[] = $node;
				}
				$this->elements = $stack;
				break;
			case 'not':
				$selector = self::unQuote($args);
				$this->elements = $this->not($selector)->stack();
				break;
			case 'slice':
				// TODO jQuery difference ?
				$args = explode(',', str_replace(', ', ',', trim($args, "\"'")));
				$start = $args[0];
				$end = $args[1] ?? null;
				if($end > 0)
					$end = $end - $start;
				$this->elements = array_slice($this->elements, (int)$start, $end);
				break;
			case 'has':
				$selector = trim($args, "\"'");
				$stack = [];
				foreach($this->stack(1) as $el) {
					if($this->find($selector, $el, true)->count())
						$stack[] = $el;
				}
				$this->elements = $stack;
				break;
			case 'submit':
			case 'reset':
				$this->elements = phpQuery::merge($this->map([$this, 'is'], "input[type=$class]", new CallbackParam()), $this->map([$this, 'is'], "button[type=$class]", new CallbackParam()));
				break;
			case 'input':
				$this->elements = $this->map([$this, 'is'], 'input', new CallbackParam())->elements;
				break;
			case 'password':
			case 'checkbox':
			case 'radio':
			case 'hidden':
			case 'image':
			case 'file':
				$this->elements = $this->map([$this, 'is'], "input[type=$class]", new CallbackParam())->elements;
				break;
			case 'parent':
				$this->elements = $this->map(function($node) {
					return $node instanceof DOMElement && $node->childNodes->length ? $node : null;
				})->elements;
				break;
			case 'empty':
				$this->elements = $this->map(function($node) {
					return $node instanceof DOMElement && $node->childNodes->length ? null : $node;
				})->elements;
				break;
			case 'disabled':
			case 'selected':
			case 'checked':
				$this->elements = $this->map([$this, 'is'], "[$class]", new CallbackParam())->elements;
				break;
			case 'enabled':
				$this->elements = $this->map(function($node) {
					return pq($node)->not(':disabled') ? $node : null;
				})->elements;
				break;
			case 'header':
				$this->elements = $this->map(function($node) {
					$is_header = isset($node->tagName) && in_array($node->tagName, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7']);
					return $is_header ? $node : null;
				})->elements;
				break;
			case 'only-child':
				$this->elements = $this->map(function($node) {
					return pq($node)->siblings()->size() == 0 ? $node : null;
				})->elements;
				break;
			case 'first-child':
				$this->elements = $this->map(function($node) {
					return pq($node)->prevAll()->size() == 0 ? $node : null;
				})->elements;
				break;
			case 'last-child':
				$this->elements = $this->map(function($node) {
					return pq($node)->nextAll()->size() == 0 ? $node : null;
				})->elements;
				break;
			case 'nth-child':
				$param = trim($args, "\"'");
				if(!$param)
					break;
				// nth-child(n+b) to nth-child(1n+b)
				if($param[0] == 'n')
					$param = '1'.$param;
				// :nth-child(index/even/odd/equation)
				if($param == 'even' || $param == 'odd') {
					$mapped = $this->map(function($node, $param) {
						$index = pq($node)->prevAll()->size() + 1;
						if($param == 'even' && ($index % 2) == 0) {
							return $node;
						} elseif($param == 'odd' && $index % 2 == 1) {
							return $node;
						} else {
							return null;
						}
					}, new CallbackParam(), $param);
				} elseif(mb_strlen($param) > 1 && $param[1] == 'n') {
					// an+b
					$mapped = $this->map(function($node, $param) {
						$prevs = pq($node)->prevAll()->size();
						$index = 1 + $prevs;
						$bum = mb_strlen($param) > 3 ? $param[3] : 0;
						$aku = $param[0];
						if($bum && $param[2] == '-') {
							$bum = -$bum;
						}
						if($aku > 0) {
							return ($index - $bum) % $aku == 0 ? $node : null;
							//return $aku*floor($index/$aku)+$bum-1 == $prevs ? $node : null;
						} elseif($aku == 0) {
							return $index == $bum ? $node : null;
						} else {
							// negative value
							return $index <= $bum ? $node : null;
						}
					}, new CallbackParam(), $param);
				} else {
					$mapped = $this->map(function($node, $index) {
						$prevs = pq($node)->prevAll()->size();
						if($prevs && $prevs == $index - 1) {
							return $node;
						} elseif(!$prevs && $index == 1) {
							return $node;
						} else {
							return null;
						}
					}, new CallbackParam(), $param);
				}
				// index
				$this->elements = $mapped->elements;
				break;
			default:
				$this->debug("Unknown pseudoclass '$class', skipping...");
		}
	}

	/**
	 * @throws \Exception
	 */
	public function is($selector, $nodes = null): bool|array|null
	{
		phpQuery::debug(['Is:', $selector]);
		if(!$selector) {
			return false;
		}
		$old_stack = $this->elements;
		if($nodes && is_array($nodes)) {
			$this->elements = $nodes;
		} elseif($nodes) {
			$this->elements = [$nodes];
		}

		$this->filter($selector, true);
		$stack = $this->elements;
		$this->elements = $old_stack;
		if($nodes) {
			return $stack;
		}
		return (bool)count($stack);
	}

	/**
	 * @throws \Exception
	 */
	public function filterCallback($callback, $skip_history = false)
	{
		if(!$skip_history) {
			$this->elementsBackup = $this->elements;
			$this->debug('Filtering by callback');
		}
		$new_stack = [];
		foreach($this->elements as $index => $node) {
			$result = phpQuery::callbackRun($callback, [$index, $node]);
			if($result)
				$new_stack[] = $node;
		}
		$this->elements = $new_stack;
		return $skip_history ? $this : $this->newInstance();
	}

	/**
	 * @throws \Exception
	 */
	public function filter($selectors, $skip_history = false)
	{

		$is_instance = function($selector, $const) {
			return $selector instanceof $const;
		};
		//if($selectors instanceof Callback OR $selectors instanceof Closure)
		if($is_instance($selectors, Callback::class) or $is_instance($selectors, Closure::class)) {
			return $this->filterCallback($selectors, $skip_history);
		}
		if(!$skip_history)
			$this->elementsBackup = $this->elements;
		$not_simple_selector = [' ', '>', '~', '+', '/'];
		if(!is_array($selectors))
			$selectors = $this->parseSelector($selectors);
		if(!$skip_history)
			$this->debug(['Filtering:', $selectors]);
		$final_stack = [];
		foreach($selectors as $selector) {
			$stack = [];
			if(!$selector)
				break;
			// avoid first space or /
			if(in_array($selector[0], $not_simple_selector))
				$selector = array_slice($selector, 1);
			// PER NODE selector chunks
			foreach($this->stack() as $node) {
				$break = false;
				foreach($selector as $sel) {
					if(!($node instanceof DOMElement)) {
						// all besides DOMElement
						if($sel[0] == '[') {
							$attr = trim($sel, '[]');
							if(mb_strpos($attr, '=')) {
								list($attr, $val) = explode('=', $attr);
								if($attr == 'nodeType' && $node->nodeType != $val)
									$break = true;
							}
						} else {
							$break = true;
						}

					} elseif($sel[0] == '#') {
						if($node->getAttribute('id') != substr($sel, 1)) {
							$break = true;
						}
						// CLASSES
					} elseif($sel[0] == '.') {
						if(!$this->matchClasses($sel, $node)) {
							$break = true;
						}
						// ATTRS
					} elseif($sel[0] == '[') {
						// strip side brackets
						$attr = trim($sel, '[]');
						if(mb_strpos($attr, '=')) {
							[$attr, $val] = explode('=', $attr);
							$val = self::unQuote($val);
							if($attr == 'nodeType') {
								if($val != $node->nodeType)
									$break = true;
							} elseif($this->isRegexp($attr)) {
								$val = extension_loaded('mbstring') && phpQuery::$mbstringSupport ? quotemeta(trim($val, '"\'')) : preg_quote(trim($val, '"\''), '@');
								// switch last character
								// quotemeta used insted of preg_quote
								// http://code.google.com/p/phpquery/issues/detail?id=76
								$pattern = match (substr($attr, -1)) {
									'^' => '^'.$val,
									'*' => '.*'.$val.'.*',
									'$' => '.*'.$val.'$',
									default => $val
								};
								// cut last character
								$attr = substr($attr, 0, -1);
								$is_match = extension_loaded('mbstring') && phpQuery::$mbstringSupport ? mb_ereg_match($pattern, $node->getAttribute($attr)) : preg_match("@$pattern@", $node->getAttribute($attr));
								if(!$is_match)
									$break = true;
							} elseif($node->getAttribute($attr) != $val)
								$break = true;
						} elseif(!$node->hasAttribute($attr))
							$break = true;
						// PSEUDO CLASSES
					} elseif($sel[0] == ':') {
						continue;
					} elseif(trim($sel)) {
						if($sel != '*') {
							// TODO namespaces
							if(isset($node->tagName)) {
								if($node->tagName != $sel)
									$break = true;
							} elseif($sel == 'html' && !$this->isRoot($node))
								$break = true;
						}
						// AVOID NON-SIMPLE SELECTORS
					} elseif(in_array($sel, $not_simple_selector)) {
						$break = true;
						$this->debug(['Skipping non simple selector', $selector]);
					}
					if($break)
						break;
				}
				// if element passed all chunks of selector - add it to new stack
				if(!$break)
					$stack[] = $node;
			}
			$tmp_stack = $this->elements;
			$this->elements = $stack;
			// PER ALL NODES selector chunks
			foreach($selector as $sel)
				// PSEUDO CLASSES
				if($sel[0] == ':')
					$this->pseudoClasses($sel);
			foreach($this->elements as $node)
				// XXX it should be merged without duplicates
				// but jQuery doesnt do that
				$final_stack[] = $node;
			$this->elements = $tmp_stack;
		}
		$this->elements = $final_stack;
		if($skip_history) {
			return $this;
		} else {
			$this->debug('Stack length after filter(): '.count($final_stack));
			return $this->newInstance();
		}
	}

	protected static function unQuote($value): string
	{
		return $value[0] == '\'' || $value[0] == '"' ? substr($value, 1, -1) : $value;
	}

	/**
	 * @throws Exception
	 */
	public function load($url, $data = null, $callback = null): phpQueryObject
	{
		if($data && !is_array($data)) {
			$callback = $data;
			$data = null;
		}
		if(mb_strpos($url, ' ') !== false) {
			$matches = null;
			if(extension_loaded('mbstring') && phpQuery::$mbstringSupport) {
				mb_ereg('^([^ ]+) (.*)$', $url, $matches);
			} else {
				$pattern = ['([^', ' ]+)', ' (.', '*)'];
				preg_match('^'.implode('', $pattern).'$', $url, $matches);
			}

			$url = $matches[1];
			$selector = $matches[2];
			$this->loadSelector = $selector;
		}
		$ajax = ['url' => $url, 'type' => $data ? 'POST' : 'GET', 'data' => $data, 'complete' => $callback, 'success' => [$this, '__loadSuccess']];
		self::ajax($ajax);
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function __loadSuccess($html): void
	{
		if($this->loadSelector) {
			$html = self::newDocument($html)->find($this->loadSelector);
			unset($this->loadSelector);
		}
		foreach($this->stack(1) as $node) {
			self::pq($node, $this->getDocumentID())->markup($html);
		}
	}

	/* @noinspection PhpUnused */
	public function css(): phpQueryObject
	{
		// TODO
		return $this;
	}

	/* @noinspection PhpUnused */
	public function show(): phpQueryObject
	{
		// TODO
		return $this;
	}

	/* @noinspection PhpUnused */
	public function hide(): phpQueryObject
	{
		// TODO
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function trigger($type, $data = []): phpQueryObject
	{
		foreach($this->elements as $node)
			phpQueryEvents::trigger($this->getDocumentID(), $type, $data, $node);
		return $this;
	}

	/* @noinspection PhpUnused */
	public function triggerHandler($type, $data = [])
	{
		// TODO;
	}

	/**
	 * @throws \Exception
	 */
	public function bind($type, $data, $callback = null): phpQueryObject
	{
		if(!isset($callback)) {
			$callback = $data;
			$data = null;
		}
		foreach($this->elements as $node)
			phpQueryEvents::add($this->getDocumentID(), $node, $type, $data, $callback);
		return $this;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function unbind($type = null, $callback = null): phpQueryObject
	{
		foreach($this->elements as $node)
			phpQueryEvents::remove($this->getDocumentID(), $node, $type, $callback);
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function change($callback = null): phpQueryObject
	{
		return ($callback) ? $this->bind('change', $callback) : $this->trigger('change');
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function submit($callback = null): phpQueryObject
	{
		if($callback)
			return $this->bind('submit', $callback);
		return $this->trigger('submit');
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function click($callback = null): phpQueryObject
	{
		if($callback)
			return $this->bind('click', $callback);
		return $this->trigger('click');
	}

	/* @noinspection PhpUnused */
	public function wrapAllOld($wrapper): phpQueryObject
	{
		$wrapper = pq($wrapper)->_clone();
		if(!$wrapper->length() || !$this->length())
			return $this;
		$wrapper->insertBefore($this->elements[0]);
		$deepest = $wrapper->elements[0];
		while($deepest->firstChild instanceof DOMElement)
			$deepest = $deepest->firstChild;
		pq($deepest)->append($this);
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function wrapAll($wrapper)
	{
		if(!$this->length())
			return $this;
		return self::pq($wrapper, $this->getDocumentID())->clone()->insertBefore($this->get(0))->map([$this, '___wrapAllCallback'])->append($this);
	}

	public function ___wrapAllCallback($node): DOMElement
	{
		$deepest = $node;
		while($deepest->firstChild instanceof DOMElement)
			$deepest = $deepest->firstChild;
		return $deepest;
	}

	/**
	 * @throws \Exception
	 */
	public function wrapAllPHP($code_before, $code_after)
	{
		return $this->slice(0, 1)->beforePHP($code_before)->end()->slice(-1)->afterPHP($code_after)->end();
	}

	/**
	 * @throws Exception
	 * @noinspection PhpUnused
	 */
	public function wrap($wrapper): phpQueryObject
	{
		foreach($this->stack() as $node)
			self::pq($node, $this->getDocumentID())->wrapAll($wrapper);
		return $this;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function wrapPHP($code_before, $code_after): phpQueryObject
	{
		foreach($this->stack() as $node)
			self::pq($node, $this->getDocumentID())->wrapAllPHP($code_before, $code_after);
		return $this;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function wrapInner($wrapper): phpQueryObject
	{
		foreach($this->stack() as $node)
			self::pq($node, $this->getDocumentID())->contents()->wrapAll($wrapper);
		return $this;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function wrapInnerPHP($code_before, $code_after): phpQueryObject
	{
		foreach($this->stack(1) as $node)
			self::pq($node, $this->getDocumentID())->contents()->wrapAllPHP($code_before, $code_after);
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function contents()
	{
		$stack = [];
		foreach($this->stack(1) as $el) {
			// FIXME (fixed) http://code.google.com/p/phpquery/issues/detail?id=56
			//			if(!isset($el->childNodes))
			//				continue;
			foreach($el->childNodes as $node) {
				$stack[] = $node;
			}
		}
		return $this->newInstance($stack);
	}

	/* @noinspection PhpUnused */
	public function contentsUnwrap(): phpQueryObject
	{
		foreach($this->stack(1) as $node) {
			if(!$node->parentNode)
				continue;
			$child_nodes = [];
			// any modification in DOM tree breaks childNodes iteration, so cache them first
			foreach($node->childNodes as $ch_node)
				$child_nodes[] = $ch_node;
			foreach($child_nodes as $ch_node)
				//				$node->parentNode->appendChild($ch_node);
				$node->parentNode->insertBefore($ch_node, $node);
			$node->parentNode->removeChild($node);
		}
		return $this;
	}
	/* @noinspection PhpUnused */
	/**
	 * @throws \Exception
	 */
	public function switchWith($markup): phpQueryObject
	{
		$markup = pq($markup, $this->getDocumentID());
		$content = null;
		foreach($this->stack(1) as $node) {
			pq($node)->contents()->toReference($content)->end()->replaceWith($markup->clone()->append($content));
		}
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function eq($num)
	{
		$old_stack = $this->elements;
		$this->elementsBackup = $this->elements;
		$this->elements = [];
		if(isset($old_stack[$num]))
			$this->elements[] = $old_stack[$num];
		return $this->newInstance();
	}

	public function size(): int
	{
		return count($this->elements);
	}

	public function length(): int
	{
		return $this->size();
	}

	public function count(): int
	{
		return $this->size();
	}

	public function end(): phpQueryObject
	{
		//		$this->elements = array_pop($this->history);
		//		return $this;
		//		$this->previous->DOM = $this->DOM;
		//		$this->previous->XPath = $this->XPath;
		return $this->previous ?? $this;
	}

	/**
	 * @throws \Exception
	 */
	public function _clone()
	{
		$new_stack = [];
		//pr(array('copy... ', $this->whois()));
		//$this->dumpHistory('copy');
		$this->elementsBackup = $this->elements;
		foreach($this->elements as $node) {
			$new_stack[] = $node->cloneNode(true);
		}
		$this->elements = $new_stack;
		return $this->newInstance();
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function replaceWithPHP($code): phpQueryObject
	{
		return $this->replaceWith(phpQuery::php($code));
	}

	/**
	 * @throws \Exception
	 */
	public function replaceWith($content): phpQueryObject
	{
		return $this->after($content)->remove();
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function replaceAll($selector): phpQueryObject
	{
		foreach(self::pq($selector, $this->getDocumentID()) as $node)
			self::pq($node, $this->getDocumentID())->after($this->_clone())->remove();
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function remove($selector = null): phpQueryObject
	{
		$loop = $selector ? $this->filter($selector)->elements : $this->elements;
		foreach($loop as $node) {
			if(!$node->parentNode)
				continue;
			if(isset($node->tagName))
				$this->debug('Removing '.$node->tagName);
			$node->parentNode->removeChild($node);
			// Mutation event
			$event = new DOMEvent(['target' => $node, 'type' => 'DOMNodeRemoved']);
			phpQueryEvents::trigger($this->getDocumentID(), $event->type, [$event], $node);
		}
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	protected function markupEvents($new_markup, $old_markup, $node): void
	{
		if($node->tagName == 'textarea' && $new_markup != $old_markup) {
			$event = new DOMEvent(['target' => $node, 'type' => 'change']);
			phpQueryEvents::trigger($this->getDocumentID(), $event->type, [$event], $node);
		}
	}

	public function markup($markup = null, $callback1 = null, $callback2 = null, $callback3 = null)
	{
		$args = func_get_args();
		$arg = ($this->documentWrapper->isXML) ? 'xml' : 'html';
		return call_user_func_array([$this, $arg], $args);
	}

	public function markupOuter($callback1 = null, $callback2 = null, $callback3 = null)
	{
		$args = func_get_args();
		$arg = ($this->documentWrapper->isXML) ? 'xmlOuter' : 'htmlOuter';
		return call_user_func_array([$this, $arg], $args);
	}

	/**
	 * @throws \Exception
	 */
	public function html($html = null, $callback1 = null, $callback2 = null, $callback3 = null)
	{
		if(isset($html)) {
			// INSERT
			$old_html = '';
			$nodes = $this->documentWrapper->import($html);
			$this->empty();
			foreach($this->stack(1) as $already_added => $node) {
				// for now, limit events for textarea
				if(($this->isXHTML() || $this->isHTML()) && $node->tagName == 'textarea')
					$old_html = pq($node, $this->getDocumentID())->markup();
				foreach($nodes as $new_node) {
					$node->appendChild($already_added ? $new_node->cloneNode(true) : $new_node);
				}
				// for now, limit events for textarea
				if(($this->isXHTML() || $this->isHTML()) && $node->tagName == 'textarea')
					$this->markupEvents($html, $old_html, $node);
			}
			return $this;
		} else {
			// FETCH
			$return = $this->documentWrapper->markup($this->elements, true);
			$args = func_get_args();
			foreach(array_slice($args, 1) as $callback) {
				$return = phpQuery::callbackRun($callback, [$return]);
			}
			return $return;
		}
	}

	public function xml($xml = null, $callback1 = null, $callback2 = null, $callback3 = null)
	{
		$args = func_get_args();
		return call_user_func_array([$this, 'html'], $args);
	}

	/**
	 * @throws \Exception
	 */
	public function htmlOuter($callback1 = null, $callback2 = null, $callback3 = null)
	{
		$markup = $this->documentWrapper->markup($this->elements);
		// pass thou callbacks
		$args = func_get_args();
		foreach($args as $callback) {
			$markup = phpQuery::callbackRun($callback, [$markup]);
		}
		return $markup;
	}

	public function xmlOuter($callback1 = null, $callback2 = null, $callback3 = null)
	{
		$args = func_get_args();
		return call_user_func_array([$this, 'htmlOuter'], $args);
	}

	public function __toString()
	{
		return $this->markupOuter();
	}

	public function php($code = null)
	{
		return $this->markupPHP($code);
	}

	public function markupPHP($code = null)
	{
		return isset($code) ? $this->markup(phpQuery::php($code)) : self::markupToPHP($this->markup());
	}

	/* @noinspection PhpUnused */
	public function markupOuterPHP(): array|string|null
	{
		return self::markupToPHP($this->markupOuter());
	}

	/**
	 * @throws \Exception
	 */
	public function children($selector = null)
	{
		$stack = [];
		foreach($this->stack(1) as $node) {
			//			foreach($node->getElementsByTagName('*') as $new_node) {
			foreach($node->childNodes as $new_node) {
				if($new_node->nodeType != 1)
					continue;
				if($selector && !$this->is($selector, $new_node))
					continue;
				if($this->elementsContainsNode($new_node, $stack))
					continue;
				$stack[] = $new_node;
			}
		}
		$this->elementsBackup = $this->elements;
		$this->elements = $stack;
		return $this->newInstance();
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function ancestors($selector = null)
	{
		return $this->children($selector);
	}

	/**
	 * @throws \Exception
	 */
	public function append($content): phpQueryObject
	{
		return $this->insert($content, __FUNCTION__);
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function appendPHP($content): phpQueryObject
	{
		return $this->insert("<php><!-- $content --></php>", 'append');
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function appendTo($seletor): phpQueryObject
	{
		return $this->insert($seletor, __FUNCTION__);
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function prepend($content): phpQueryObject
	{
		return $this->insert($content, __FUNCTION__);
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function prependPHP($content): phpQueryObject
	{
		return $this->insert("<php><!-- $content --></php>", 'prepend');
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function prependTo($seletor): phpQueryObject
	{
		return $this->insert($seletor, __FUNCTION__);
	}

	/**
	 * @throws \Exception
	 */
	public function before($content): phpQueryObject
	{
		return $this->insert($content, __FUNCTION__);
	}

	/**
	 * @throws \Exception
	 */
	public function beforePHP($content): phpQueryObject
	{
		return $this->insert("<php><!-- $content --></php>", 'before');
	}

	/**
	 * @throws \Exception
	 */
	public function insertBefore($seletor): phpQueryObject
	{
		return $this->insert($seletor, __FUNCTION__);
	}

	/**
	 * @throws \Exception
	 */
	public function after($content): phpQueryObject
	{
		return $this->insert($content, __FUNCTION__);
	}

	/**
	 * @throws \Exception
	 */
	public function afterPHP($content): phpQueryObject
	{
		return $this->insert("<php><!-- $content --></php>", 'after');
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function insertAfter($seletor): phpQueryObject
	{
		return $this->insert($seletor, __FUNCTION__);
	}

	/**
	 * @throws \Exception
	 */
	public function insert($target, $type): phpQueryObject
	{
		$this->debug("Inserting data with '$type'");
		$first_child = $next_sibling = '';
		$to = false;
		switch($type) {
			case 'appendTo':
			case 'prependTo':
			case 'insertBefore':
			case 'insertAfter':
				$to = true;
		}
		$insert_from = $insert_to = [];
		switch(gettype($target)) {
			case 'string':
				if($to) {
					// INSERT TO
					$insert_from = $this->elements;
					if(self::isMarkup($target)) {
						// $target is new markup, import it
						$insert_to = $this->documentWrapper->import($target);
						// insert into selected element
					} else {
						// $tagret is a selector
						$this_stack = $this->elements;
						$this->toRoot();
						$insert_to = $this->find($target)->elements;
						$this->elements = $this_stack;
					}
				} else {
					// INSERT FROM
					$insert_to = $this->elements;
					$insert_from = $this->documentWrapper->import($target);
				}
				break;
			case 'object':
				{
					// phpQuery
					if($target instanceof self) {
						if($to) {
							$insert_to = $target->elements;
							if($this->documentFragment && $this->stackIsRoot()) {
								// get all body children
								//							$loop = $this->find('body > *')->elements;
								// TODO test it, test it hard...
								//							$loop = $this->newInstance($this->root)->find('> *')->elements;
								$loop = $this->root->childNodes;
							} else {
								$loop = $this->elements;
							}
							// import nodes if needed
							$insert_from = $this->getDocumentID() == $target->getDocumentID() ? $loop : $target->documentWrapper->import($loop);
						} else {
							$insert_to = $this->elements;
							if($target->documentFragment && $target->stackIsRoot()) {
								// get all body children
								//							$loop = $target->find('body > *')->elements;
								$loop = $target->root->childNodes;
							} else {
								$loop = $target->elements;
							}
							// import nodes if needed
							$insert_from = $this->getDocumentID() == $target->getDocumentID() ? $loop : $this->documentWrapper->import($loop);
						}
						// DOMNODE
					} elseif($target instanceof DOMNode) {
						// import node if needed
						//					if($target->ownerDocument != $this->DOM)
						//						$target = $this->DOM->importNode($target, true);
						if($to) {
							$insert_to = [$target];
							if($this->documentFragment && $this->stackIsRoot())
								// get all body children
								$loop = $this->root->childNodes; //							$loop = $this->find('body > *')->elements;
							else
								$loop = $this->elements;
							foreach($loop as $from_node)
								// import nodes if needed
								$insert_from[] = !$from_node->ownerDocument->isSameNode($target->ownerDocument) ? $target->ownerDocument->importNode($from_node, true) : $from_node;
						} else {
							// import node if needed
							if(!$target->ownerDocument->isSameNode($this->document))
								$target = $this->document->importNode($target, true);
							$insert_to = $this->elements;
							$insert_from[] = $target;
						}
					}
				}
				break;
		}
		phpQuery::debug('From '.count($insert_from).'; To '.count($insert_to).' nodes');
		foreach($insert_to as $insert_number => $to_node) {
			// we need static relative elements in some cases
			switch($type) {
				case 'prependTo':
				case 'prepend':
					$first_child = $to_node->firstChild;
					break;
				case 'insertAfter':
				case 'after':
					$next_sibling = $to_node->nextSibling;
					break;
			}
			foreach($insert_from as $from_node) {
				// clone if inserted already before
				$insert = $insert_number ? $from_node->cloneNode(true) : $from_node;
				switch($type) {
					case 'appendTo':
					case 'append':
						//						$to_node->insertBefore(
						//							$from_node,
						//							$to_node->lastChild->nextSibling
						//						);
						$to_node->appendChild($insert);
						break;
					case 'prependTo':
					case 'prepend':
						$to_node->insertBefore($insert, $first_child);
						break;
					case 'insertBefore':
					case 'before':
						if(!$to_node->parentNode) {
							throw new Exception(__LINE__.': '.__METHOD__." -> No parentNode, can't do $type()");
						} else {
							$to_node->parentNode->insertBefore($insert, $to_node);
						}
						break;
					case 'insertAfter':
					case 'after':
						if(!$to_node->parentNode) {
							throw new Exception(__LINE__.': '.__METHOD__." -> No parentNode, can't do $type()");
						} else {
							$to_node->parentNode->insertBefore($insert, $next_sibling);
						}
						break;
				}
				// Mutation event
				$event = new DOMEvent(['target' => $insert, 'type' => 'DOMNodeInserted']);
				phpQueryEvents::trigger($this->getDocumentID(), $event->type, [$event], $insert);
			}
		}
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function index($subject): int
	{
		$index = -1;
		$subject = $subject instanceof phpQueryObject ? $subject->elements[0] : $subject;
		foreach($this->newInstance() as $key => $node) {
			if($node->isSameNode($subject))
				$index = $key;
		}
		return $index;
	}

	/**
	 * @throws \Exception
	 */
	public function slice($start, $end = null)
	{
		if($end > 0)
			$end = $end - $start;
		return $this->newInstance(array_slice($this->elements, $start, $end));
	}

	/**
	 * @throws \Exception
	 */
	public function reverse()
	{
		$this->elementsBackup = $this->elements;
		$this->elements = array_reverse($this->elements);
		return $this->newInstance();
	}

	/**
	 * @throws \Exception
	 */
	public function text($text = null, $callback1 = null, $callback2 = null, $callback3 = null)
	{
		if(isset($text))
			return $this->html(htmlspecialchars($text));
		$args = func_get_args();
		$args = array_slice($args, 1);
		$return = '';
		foreach($this->elements as $node) {
			$text = $node->textContent;
			if(count($this->elements) > 1 && $text)
				$text .= '\n';
			foreach($args as $callback) {
				$text = phpQuery::callbackRun($callback, [$text]);
			}
			$return .= $text;
		}
		return $return;
	}

	/**
	 * @throws Exception
	 * @noinspection PhpUnused
	 */
	public function plugin($class, $file = null): phpQueryObject
	{
		phpQuery::plugin($class, $file);
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function __call($method, $args)
	{
		$alias_methods = ['clone', 'empty'];
		if(isset(phpQuery::$extendMethods[$method])) {
			array_unshift($args, $this);
			return phpQuery::callbackRun(phpQuery::$extendMethods[$method], $args);
		} elseif(isset(phpQuery::$pluginsMethods[$method])) {
			array_unshift($args, $this);
			$class = phpQuery::$pluginsMethods[$method];
			$real_class = "phpQueryObjectPlugin_$class";
			$return = call_user_func_array([$real_class, $method], $args);
			// XXX deprecate ?
			return $return ?? $this;
		} elseif(in_array($method, $alias_methods)) {
			return call_user_func_array([$this, '_'.$method], $args);
		} else
			throw new Exception(__LINE__.': '.__METHOD__." -> Method '$method' doesnt exist");
	}

	/**
	 * @throws \Exception
	 */
	public function nextSelector($selector = null)
	{
		return $this->newInstance($this->getElementSiblings('nextSibling', $selector, true));
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function prevSelector($selector = null)
	{
		return $this->prev($selector);
	}

	/**
	 * @throws \Exception
	 */
	public function prev($selector = null)
	{
		return $this->newInstance($this->getElementSiblings('previousSibling', $selector, true));
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function prevAll($selector = null)
	{
		return $this->newInstance($this->getElementSiblings('previousSibling', $selector));
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function nextAll($selector = null)
	{
		return $this->newInstance($this->getElementSiblings('nextSibling', $selector));
	}

	/**
	 * @throws \Exception
	 */
	protected function getElementSiblings($direction, $selector = null, $limitToOne = false): array
	{
		$stack = [];
		foreach($this->stack() as $node) {
			$test = $node;
			while(isset($test->{$direction}) && $test->{$direction}) {
				$test = $test->{$direction};
				if(!$test instanceof DOMElement)
					continue;
				$stack[] = $test;
				if($limitToOne)
					break;
			}
		}
		if($selector) {
			$stack_old = $this->elements;
			$this->elements = $stack;
			$stack = $this->filter($selector, true)->stack();
			$this->elements = $stack_old;
		}
		return $stack;
	}

	/**
	 * @throws \Exception
	 */
	public function siblings($selector = null)
	{
		$stack = [];
		$siblings = array_merge($this->getElementSiblings('previousSibling', $selector), $this->getElementSiblings('nextSibling', $selector));
		foreach($siblings as $node) {
			if(!$this->elementsContainsNode($node, $stack))
				$stack[] = $node;
		}
		return $this->newInstance($stack);
	}

	/**
	 * @throws \Exception
	 */
	public function not($selector = null)
	{
		if(is_string($selector)) {
			phpQuery::debug(['not', $selector]);
		} else {
			phpQuery::debug('not');
		}
		$stack = [];
		if($selector instanceof self || $selector instanceof DOMNode) {
			foreach($this->stack() as $node) {
				if($selector instanceof self) {
					$match_found = false;
					foreach($selector->stack() as $not_node) {
						if($not_node->isSameNode($node))
							$match_found = true;
					}
					if(!$match_found)
						$stack[] = $node;
				} elseif($selector instanceof DOMNode) {
					if(!$selector->isSameNode($node))
						$stack[] = $node;
				} elseif(!$this->is($selector)) {
					$stack[] = $node;
				}
			}
		} else {
			$org_stack = $this->stack();
			$matched = $this->filter($selector, true)->stack();
			foreach($org_stack as $node)
				if(!$this->elementsContainsNode($node, $matched))
					$stack[] = $node;
		}
		return $this->newInstance($stack);
	}

	/**
	 * @throws \Exception
	 */
	public function add($selector = null)
	{
		if(!$selector)
			return $this;
		$this->elementsBackup = $this->elements;
		$found = self::pq($selector, $this->getDocumentID());
		$this->merge($found->elements);
		return $this->newInstance();
	}

	protected function merge(): void
	{
		foreach(func_get_args() as $nodes) {
			foreach($nodes as $new_node) {
				if(!$this->elementsContainsNode($new_node)) {
					$this->elements[] = $new_node;
				}
			}
		}
	}

	protected function elementsContainsNode($nodeToCheck, $elements_stack = null): bool
	{
		$loop = !is_null($elements_stack) ? $elements_stack : $this->elements;
		foreach($loop as $node) {
			if($node->isSameNode($nodeToCheck))
				return true;
		}
		return false;
	}

	/**
	 * @throws \Exception
	 */
	public function parent($selector = null)
	{
		$stack = [];
		foreach($this->elements as $node)
			if($node->parentNode && !$this->elementsContainsNode($node->parentNode, $stack))
				$stack[] = $node->parentNode;
		$this->elementsBackup = $this->elements;
		$this->elements = $stack;
		if($selector)
			$this->filter($selector, true);
		return $this->newInstance();
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function parents($selector = null)
	{
		$stack = [];
		if(!$this->elements)
			$this->debug('parents() - stack empty');
		foreach($this->elements as $node) {
			$test = $node;
			while($test->parentNode) {
				$test = $test->parentNode;
				if($this->isRoot($test))
					break;
				if(!$this->elementsContainsNode($test, $stack)) {
					$stack[] = $test;
				}
			}
		}
		$this->elementsBackup = $this->elements;
		$this->elements = $stack;
		if($selector)
			$this->filter($selector, true);
		return $this->newInstance();
	}

	public function stack($node_types = null): array
	{
		if(!isset($node_types))
			return $this->elements;
		if(!is_array($node_types))
			$node_types = [$node_types];
		$return = [];
		foreach($this->elements as $node) {
			if(in_array($node->nodeType, $node_types))
				$return[] = $node;
		}
		return $return;
	}

	/**
	 * @throws \Exception
	 */
	protected function attrEvents($attr, $old_attr, $old_value, $node): void
	{
		// skip events for XML documents
		if(!$this->isXHTML() && !$this->isHTML())
			return;
		$event = null;
		// identify
		$is_input_value = $node->tagName == 'input' && (in_array($node->getAttribute('type'), ['text', 'password', 'hidden']) || !$node->getAttribute('type'));
		$is_radio = $node->tagName == 'input' && $node->getAttribute('type') == 'radio';
		$is_checkbox = $node->tagName == 'input' && $node->getAttribute('type') == 'checkbox';
		$is_option = $node->tagName == 'option';
		if($is_input_value && $attr == 'value' && $old_value != $node->getAttribute($attr)) {
			$event = new DOMEvent(['target' => $node, 'type' => 'change']);
		} elseif(($is_radio || $is_checkbox) && $attr == 'checked' && (// check
				(!$old_attr && $node->hasAttribute($attr)) // un-check
				|| (!$node->hasAttribute($attr) && $old_attr))) {
			$event = new DOMEvent(['target' => $node, 'type' => 'change']);
		} elseif($is_option && $node->parentNode && $attr == 'selected' && (// select
				(!$old_attr && $node->hasAttribute($attr)) // un-select
				|| (!$node->hasAttribute($attr) && $old_attr))) {
			$event = new DOMEvent(['target' => $node->parentNode, 'type' => 'change']);
		}
		if($event) {
			phpQueryEvents::trigger($this->getDocumentID(), $event->type, [$event], $node);
		}
	}

	/**
	 * @throws \Exception
	 */
	public function attr($attr = null, $value = null): phpQueryObject|array|string|null
	{
		foreach($this->stack(1) as $node) {
			if(!is_null($value)) {
				$loop = $attr == '*' ? $this->getNodeAttrs($node) : [$attr];
				foreach($loop as $aku) {
					$old_value = $node->getAttribute($aku);
					$old_attr = $node->hasAttribute($aku);
					// TODO raises an error when charset other than UTF-8
					// while document's charset is also not UTF-8
					@$node->setAttribute($aku, $value);
					$this->attrEvents($aku, $old_attr, $old_value, $node);
				}
			} elseif($attr == '*') {
				// jQuery difference
				$return = [];
				foreach($node->attributes as $key => $val)
					$return[$key] = $val->value;
				return $return;
			} else
				return $node->getAttribute($attr);
		}
		return ($value) ? $this : '';
	}

	protected function getNodeAttrs($node): array
	{
		$return = [];
		foreach($node->attributes as $key => $value)
			$return[] = $key;
		return $return;
	}

	/* @noinspection PhpUnused */
	public function attrPHP($attr, $code): phpQueryObject|array|string|null
	{
		if(!is_null($code)) {
			$value = '<'.'?php '.$code.' ?'.'>';
			// TODO tempolary solution
			// http://code.google.com/p/phpquery/issues/detail?id=17
			//			if(function_exists('mb_detect_encoding') && mb_detect_encoding($value) == 'ASCII')
			//				$value	= mb_convert_encoding($value, 'UTF-8', 'HTML-ENTITIES');
		}
		foreach($this->stack(1) as $node) {
			if(!is_null($code)) {
				//				$attr_node = $this->DOM->createAttribute($attr);
				$node->setAttribute($attr, $value);
				//				$attr_node->value = $value;
				//				$node->appendChild($attr_node);
			} elseif($attr == '*') {
				// jQuery diff
				$return = [];
				foreach($node->attributes as $key => $val) {
					$return[$key] = $val->value;
				}
				return $return;
			} else {
				return $node->getAttribute($attr);
			}
		}
		return $this;
	}

	/**
	 * Removes an attribute from each matched element.
	 * @param string $attr An attribute to remove, it can be a space-separated list of attributes.
	 * @return phpQueryObject
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function removeAttr(string $attr): phpQueryObject
	{
		foreach($this->stack(1) as $node) {
			$loop = $attr == '*' ? $this->getNodeAttrs($node) : [$attr];
			foreach($loop as $aku) {
				$old_value = $node->getAttribute($aku);
				$node->removeAttribute($aku);
				$this->attrEvents($aku, $old_value, null, $node);
			}
		}
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function val($val = null)
	{
		if(!isset($val)) {
			if($this->eq(0)->is('select')) {
				$selected = $this->eq(0)->find('option[selected=selected]');
				if($selected->is('[value]')) {
					return $selected->attr('value');
				} else {
					return $selected->text();
				}
			} elseif($this->eq(0)->is('textarea')) {
				return $this->eq(0)->markup();
			} else {
				return $this->eq(0)->attr('value');
			}
		} else {
			$val_ = null;
			foreach($this->stack(1) as $node) {
				$node = pq($node, $this->getDocumentID());
				if(is_array($val) && in_array($node->attr('type'), ['checkbox', 'radio'])) {
					$is_checked = in_array($node->attr('value'), $val) || in_array($node->attr('name'), $val);
					if($is_checked) {
						$node->attr('checked', 'checked');
					} else {
						$node->removeAttr('checked');
					}
				} elseif($node->get(0)->tagName == 'select') {
					if(!isset($val_)) {
						$val_ = [];
						if(!is_array($val)) {
							$val_ = [(string)$val];
						} else {
							foreach($val as $value) {
								$val_[] = $value;
							}
						}
					}
					foreach($node['option']->stack(1) as $option) {
						$option = pq($option, $this->getDocumentID());
						// XXX: workaround for string comparsion, see issue #96
						// http://code.google.com/p/phpquery/issues/detail?id=96
						$selected = is_null($option->attr('value')) ? in_array($option->markup(), $val_) : in_array($option->attr('value'), $val_);
						if($selected) {
							$option->attr('selected', 'selected');
						} else {
							$option->removeAttr('selected');
						}
					}
				} elseif($node->get(0)->tagName == 'textarea') {
					$node->markup($val);
				} else {
					$node->attr('value', $val);
				}
			}
		}
		return $this;
	}

	public function andSelf(): phpQueryObject
	{
		$this->elements = array_merge($this->elements, (isset($this->previous)) ? $this->previous->elements : []);
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function addClass($class_name): phpQueryObject
	{
		if(!$class_name)
			return $this;
		foreach($this->stack(1) as $node) {
			if(!$this->is(".$class_name", $node))
				$node->setAttribute('class', trim($node->getAttribute('class').' '.$class_name));
		}
		return $this;
	}

	/* @noinspection PhpUnused */
	public function addClassPHP($class_name): phpQueryObject
	{
		foreach($this->stack(1) as $node) {
			$classes = $node->getAttribute('class');
			$new_value = $classes ? $classes.' <'.'?php '.$class_name.' ?'.'>' : '<'.'?php '.$class_name.' ?'.'>';
			$node->setAttribute('class', $new_value);
		}
		return $this;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function hasClass($class_name): bool
	{
		foreach($this->stack(1) as $node) {
			if($this->is(".$class_name", $node))
				return true;
		}
		return false;
	}

	public function removeClass($class_name): phpQueryObject
	{
		foreach($this->stack(1) as $node) {
			$classes = explode(' ', $node->getAttribute('class'));
			if(in_array($class_name, $classes)) {
				$classes = array_diff($classes, [$class_name]);
				if($classes) {
					$node->setAttribute('class', implode(' ', $classes));
				} else {
					$node->removeAttribute('class');
				}
			}
		}
		return $this;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function toggleClass($class_name): phpQueryObject
	{
		foreach($this->stack(1) as $node) {
			if($this->is($node, '.'.$class_name)) {
				$this->removeClass($class_name);
			} else {
				$this->addClass($class_name);
			}
		}
		return $this;
	}

	/* @noinspection PhpUnused */
	public function _empty(): phpQueryObject
	{
		foreach($this->stack(1) as $node) {
			// thx to 'dave at dgx dot cz'
			$node->nodeValue = '';
		}
		return $this;
	}

	public function each($callback, $param1 = null, $param2 = null, $param3 = null): phpQueryObject
	{
		$param_structure = null;
		if(func_num_args() > 1) {
			$param_structure = func_get_args();
			$param_structure = array_slice($param_structure, 1);
		}
		foreach($this->elements as $val)
			phpQuery::callbackRun($callback, [$val], $param_structure);
		return $this;
	}

	public function callback($callback, $param1 = null, $param2 = null, $param3 = null): phpQueryObject
	{
		$params = func_get_args();
		$params[0] = $this;
		phpQuery::callbackRun($callback, $params);
		return $this;
	}

	/**
	 * @throws \Exception
	 */
	public function map($callback, $param1 = null, $param2 = null, $param3 = null)
	{
		$params = func_get_args();
		array_unshift($params, $this->elements);
		return $this->newInstance(call_user_func_array(['phpQuery', 'map'], $params)//			phpQuery::map($this->elements, $callback)
		);
	}

	/**
	 * @throws \Exception
	 */
	public function data($node, $name = null): phpQueryObject
	{
		if(!isset($value)) {
			// TODO? implement specific jQuery behavior od returning parent values
			// is child which we look up doesn't exist
			return self::data($this->get(0), $node, $name, $this->getDocumentID());
		} else {
			foreach($this as $node_)
				self::data($node_, $node, $value, $this->getDocumentID());
			return $this;
		}
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function removeData($node): phpQueryObject
	{
		foreach($this as $node_)
			static::removeData($node_, $node, $this->getDocumentID());
		return $this;
	}
	// INTERFACE IMPLEMENTATIONS

	// ITERATOR INTERFACE
	public function rewind(): void
	{
		$this->debug('iterating foreach');
		//		phpQuery::selectDocument($this->getDocumentID());
		$this->elementsBackup = $this->elements;
		$this->elementsInterator = $this->elements;
		$this->valid = isset($this->elements[0]);
		// 		$this->elements = $this->valid
		// 			? [$this->elements[0]]
		// 			: [];
		$this->current = 0;
	}

	public function current(): mixed
	{
		return $this->elementsInterator[$this->current];
	}

	public function key(): int
	{
		return $this->current;
	}

	/**
	 * @throws \Exception
	 */
	public function next($css_selector = null): void
	{
		//		if($css_selector || $this->valid)
		//			return $this->nextSelector($css_selector);
		$this->valid = isset($this->elementsInterator[$this->current + 1]);
		if(!$this->valid && $this->elementsInterator) {
			$this->elementsInterator = [];
		} elseif($this->valid) {
			$this->current++;
		} else {
			$this->nextSelector($css_selector);
		}
	}

	public function valid(): bool
	{
		return $this->valid;
	}

	/**
	 * @throws \Exception
	 */
	public function offsetExists($offset): bool
	{
		return $this->find($offset)->size() > 0;
	}

	/**
	 * @throws \Exception
	 */
	public function offsetGet($offset): phpQueryObject
	{
		return $this->find($offset);
	}

	/**
	 * @throws \Exception
	 */
	public function offsetSet($offset, $value): void
	{
		//		$this->find($offset)->replaceWith($value);
		$this->find($offset)->html($value);
	}

	/**
	 * @throws \Exception
	 */
	public function offsetUnset($offset): void
	{
		// empty
		throw new Exception(__LINE__.': '.__METHOD__.' -> Cant do unset, use array interface only for calling queries and replacing HTML.');
	}

	protected function getNodeXpath($one_node = null)
	{
		$return = [];
		$loop = $one_node ? [$one_node] : $this->elements;
		foreach($loop as $node) {
			if($node instanceof DOMDocument) {
				$return[] = '';
				continue;
			}
			$xpath = [];
			while(!($node instanceof DOMDocument)) {
				$num = 1;
				$sibling = $node;
				while($sibling->previousSibling) {
					$sibling = $sibling->previousSibling;
					$is_element = $sibling instanceof DOMElement;
					if($is_element && $sibling->tagName == $node->tagName)
						$num++;
				}
				$xpath[] = $this->isXML() ? "*[local-name()='".$node->tagName."'][$num]" : $node->tagName."[$num]";
				$node = $node->parentNode;
			}
			$xpath = join('/', array_reverse($xpath));
			$return[] = '/'.$xpath;
		}
		return $one_node ? $return[0] : $return;
	}

	// HELPERS
	public function whois($one_node = null)
	{
		$return = [];
		$loop = $one_node ? [$one_node] : $this->elements;
		foreach($loop as $node) {
			if(isset($node->tagName)) {
				$tag = in_array($node->tagName, ['php', 'js']) ? strtoupper($node->tagName) : $node->tagName;
				$return[] = $tag.($node->getAttribute('id') ? '#'.$node->getAttribute('id') : '').($node->getAttribute('class') ? '.'.join('.', explode(' ', $node->getAttribute('class'))) : '').($node->getAttribute('name') ? '[name="'.$node->getAttribute('name').'"]' : '').($node->getAttribute('value') && !str_contains($node->getAttribute('value'), '<'.'?php') ? '[value="'.substr(str_replace('\n', '', $node->getAttribute('value')), 0, 15).'"]' : '').($node->getAttribute('value') && str_contains($node->getAttribute('value'), '<'.'?php') ? '[value=PHP]' : '').($node->getAttribute('selected') ? '[selected]' : '').($node->getAttribute('checked') ? '[checked]' : '');
			} elseif($node instanceof DOMText) {
				if(trim($node->textContent))
					$return[] = 'Text:'.substr(str_replace('\n', ' ', $node->textContent), 0, 15);
			}
		}
		return $one_node && isset($return[0]) ? $return[0] : $return;
	}

	/**
	 * @throws \Exception
	 */
	public function dump(): phpQueryObject
	{
		print 'DUMP #'.(phpQuery::$dumpCount++).' ';
		phpQuery::$debug = false;
		var_dump($this->htmlOuter());
		return $this;
	}

	/* @noinspection PhpUnused */
	public function dumpWhois(): phpQueryObject
	{
		print 'DUMP #'.(phpQuery::$dumpCount++).' ';
		var_dump('whois', $this->whois());
		return $this;
	}

	/* @noinspection PhpUnused */
	public function dumpLength(): phpQueryObject
	{
		print 'DUMP #'.(phpQuery::$dumpCount++).' ';
		var_dump('length', $this->length());
		return $this;
	}

	/* @noinspection PhpUnused */
	public function dumpTree($html = true, $title = true): phpQueryObject
	{
		$output = $title ? 'DUMP #'.(phpQuery::$dumpCount++).' \n' : '';
		foreach($this->stack() as $node)
			$output .= $this->__dumpTree($node);
		print $html ? nl2br(str_replace(' ', '&nbsp;', $output)) : $output;
		return $this;
	}

	private function __dumpTree($node, $intend = 0): string
	{
		$whois = $this->whois($node);
		$return = '';
		if($whois)
			$return .= str_repeat(' - ', $intend).$whois.'\n';
		if(isset($node->childNodes))
			foreach($node->childNodes as $ch_node)
				$return .= $this->__dumpTree($ch_node, $intend + 1);
		return $return;
	}

	/**
	 * @throws \Exception
	 * @noinspection PhpUnused
	 */
	public function dumpDie(): void
	{
		print __FILE__.':'.__LINE__;
		var_dump($this->htmlOuter());
	}
}