<?php
/*
Date: 14.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/
declare(strict_types = 1);

namespace Cpsync\Parser\phpQuery;

use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Exception;

use Cpsync\Parser\phpQuery;

class DOMDocWrapper
{

	/* @noinspection PhpUnused */
	public int $uuid = 0;
	/* @noinspection PhpUnused */
	public array $eventsNodes = [];
	/* @noinspection PhpUnused */
	public array $dataNodes = [];
	/* @noinspection PhpUnused */
	public array $eventsGlobal = [];
	/* @noinspection PhpUnused */
	public array $frames = [];

	public DOMNode|DOMDocument|null $document = null;
	public mixed $docid;
	public string $contentType;
	public DOMXPath $xpath;
	public array $data;
	public array $events = [];
	public mixed $root = null;
	public bool $isDocumentFragment;
	public bool $isXML = false;
	public bool $isXHTML = false;
	public bool $isHTML = false;
	public string $charset;

	/**
	 * @throws \Exception
	 */
	public function __construct($markup = null, string $content_type = null, $newDocumentID = null)
	{
		if(isset($markup)) {
			$this->load($markup, $content_type);
		}
		$this->docid = $newDocumentID ?? md5(microtime());
	}

	/**
	 * @throws \Exception
	 */
	public function load($markup, string $content_type = null): bool
	{
		$loaded = false;
		$this->contentType = $content_type ? strtolower($content_type) : 'text/html';
		if($markup instanceof DOMDocument) {
			$this->document = $markup;
			$this->root = $this->document;
			$this->charset = $this->document->encoding;
			// TODO isDocumentFragment
		} else {
			$loaded = $this->loadMarkup($markup);
		}
		if($loaded) {
			$this->document->preserveWhiteSpace = true;
			$this->xpath = new DOMXPath($this->document);
			$this->afterMarkupLoad();
			return true;
		}
		return false;
	}

	protected function afterMarkupLoad(): void
	{
		if($this->isXHTML) {
			$this->xpath->registerNamespace('html', 'http://www.w3.org/1999/xhtml');
		}
	}

	/**
	 * @throws \Exception
	 */
	protected function loadMarkup($markup): bool
	{
		$loaded = false;
		if($this->contentType) {
			self::debug('Load markup for content type '.$this->contentType);
			// content determined by contentType
			[$content_type, $charset] = $this->contentTypeToArray($this->contentType);
			switch($content_type) {
				case 'text/html':
					{
						phpQuery::debug('Loading HTML, content type '.$this->contentType);
						$loaded = $this->loadMarkupHTML($markup, $charset);
					}
					break;
				case 'text/xml':
				case 'application/xhtml+xml':
					{
						phpQuery::debug('Loading XML, content type '.$this->contentType);
						$loaded = $this->loadMarkupXML($markup, $charset);
					}
					break;
				default:
				{
					// for feeds or anything that sometimes doesn't use text/xml
					if(str_contains('xml', $this->contentType)) {
						phpQuery::debug('Loading XML, content type '.$this->contentType);
						$loaded = $this->loadMarkupXML($markup, $charset);
					} else {
						phpQuery::debug('Could not determine document type from content type '.$this->contentType);
					}
				}

			}
		} // content type autodetection
		elseif($this->isXML($markup)) {
			phpQuery::debug('Loading XML, isXML() == true');
			$loaded = $this->loadMarkupXML($markup);
			if(!$loaded && $this->isXHTML) {
				phpQuery::debug('Loading as XML failed, trying to load as HTML, isXHTML == true');
				$loaded = $this->loadMarkupHTML($markup);
			}
		} else {
			phpQuery::debug('Loading HTML, isXML() == false');
			$loaded = $this->loadMarkupHTML($markup);
		}
		return $loaded;
	}

	protected function loadMarkupReset(): void
	{
		$this->isXML = $this->isXHTML = $this->isHTML = false;
	}

	protected function documentCreate($charset, $version = '1.0'): void
	{
		if(!$version)
			$version = '1.0';
		$this->document = new DOMDocument($version, $charset);
		$this->charset = $this->document->encoding;
		//		$this->document->encoding = $charset;
		$this->document->formatOutput = true;
		$this->document->preserveWhiteSpace = true;
	}

	protected function loadMarkupHTML($markup, $requested_charset = null): bool
	{
		if(phpQuery::$debug) {
			phpQuery::debug('Full markup load (HTML): '.substr($markup, 0, 250));
		}
		$this->loadMarkupReset();
		$this->isHTML = true;
		if(!isset($this->isDocumentFragment)) {
			$this->isDocumentFragment = self::isDocFragmentHTML($markup);
		}
		$charset = null;
		$document_charset = $this->charsetFromHTML($markup);
		$add_doc_charset = false;
		if($document_charset) {
			$charset = $document_charset;
			$markup = $this->charsetFixHTML($markup);
		} elseif($requested_charset) {
			$charset = $requested_charset;
			$requested_charset = strtoupper($requested_charset);
		}
		if(!$charset) {
			$charset = phpQuery::$defaultCharset;
		}
		// HTTP 1.1 says that the default charset is ISO-8859-1
		// @see http://www.w3.org/International/O-HTTP-charset
		if(!$document_charset) {
			$document_charset = 'ISO-8859-1';
			$add_doc_charset = true;
		}
		// Should be careful here, still need 'magic encoding detection' since lots of pages have other 'default encoding'
		// Worse, some pages can have mixed encodings... we'll try not to worry about that

		$document_charset = strtoupper($document_charset);
		phpQuery::debug("DOC: $document_charset REQ: $requested_charset");
		if($requested_charset && $document_charset && $requested_charset !== $document_charset) {
			phpQuery::debug('CHARSET CONVERT');
			// Document Encoding Conversion
			// http://code.google.com/p/phpquery/issues/detail?id=86
			if(function_exists('mb_detect_encoding')) {
				$possible_charsets = [$document_charset, $requested_charset, 'AUTO'];
				$doc_encoding = mb_detect_encoding($markup, implode(', ', $possible_charsets));
				if(!$doc_encoding) {
					$doc_encoding = $document_charset; // ok trust the document
				}
				phpQuery::debug("DETECTED '$doc_encoding'");
				// Detected does not match what document says...
				//if($doc_encoding !== $document_charset) {
				// Tricky..
				//}
				if($doc_encoding !== $requested_charset) {
					phpQuery::debug("CONVERT $doc_encoding => $requested_charset");
					$markup = mb_convert_encoding($markup, $requested_charset, $doc_encoding);
					$markup = $this->charsetAppendToHTML($markup, $requested_charset);
					$charset = $requested_charset;
				}
			} else {
				phpQuery::debug('TODO: charset conversion without mbstring...');
			}
		}
		if($this->isDocumentFragment) {
			phpQuery::debug("Full markup load (HTML), DocumentFragment detected, using charset '$charset'");
			$return = $this->docFragLoadMarkup($this, $charset, $markup);
		} else {
			if($add_doc_charset) {
				phpQuery::debug("Full markup load (HTML), appending charset: '$charset'");
				$markup = $this->charsetAppendToHTML($markup, $charset);
			}
			phpQuery::debug("Full markup load (HTML), documentCreate('$charset')");
			$this->documentCreate($charset);
			libxml_use_internal_errors(true);
			$return = @$this->document->loadHTML($markup);
			libxml_clear_errors();
			if($return)
				$this->root = $this->document;
		}
		if($return && !$this->contentType)
			$this->contentType = 'text/html';
		return $return;
	}

	/**
	 * @throws Exception
	 */
	protected function loadMarkupXML($markup, $requested_charset = null): bool
	{
		if(phpQuery::$debug)
			phpQuery::debug('Full markup load (XML): '.substr($markup, 0, 250));
		$this->loadMarkupReset();
		$this->isXML = true;
		// check agains XHTML in contentType or markup
		$is_type_xhtml = $this->isXHTML();
		$is_markup_xhtml = $this->isXHTML($markup);
		if($is_type_xhtml || $is_markup_xhtml) {
			self::debug('Full markup load (XML), XHTML detected');
			$this->isXHTML = true;
		}
		// determine document fragment
		if(!isset($this->isDocumentFragment))
			$this->isDocumentFragment = $this->isXHTML ? self::isDocFragmentXHTML($markup) : self::isDocFragmentXML($markup);
		// this charset will be used
		$charset = null;
		// charset from XML declaration @var string
		$document_charset = $this->charsetFromXML($markup);
		if(!$document_charset) {
			if($this->isXHTML) {
				// this is XHTML, try to get charset from content-type meta header
				$document_charset = $this->charsetFromHTML($markup);
				if($document_charset) {
					phpQuery::debug("Full markup load (XML), appending XHTML charset '$document_charset'");
					/** @var Object $this */
					$this->charsetAppendToXML($markup, $document_charset);
					$charset = $document_charset;
				}
			}
			if(!$document_charset) {
				// if still no document charset...
				$charset = $requested_charset;
			}
		} elseif($requested_charset) {
			$charset = $requested_charset;
		}
		if(!$charset) {
			$charset = phpQuery::$defaultCharset;
		}
		//if($requested_charset && $document_charset && $requested_charset != $document_charset) {
		// TODO place for charset conversion
		//			$charset = $requested_charset;
		//}
		if($this->isDocumentFragment) {
			phpQuery::debug("Full markup load (XML), DocumentFragment detected, using charset '$charset'");
			$return = $this->docFragLoadMarkup($this, $charset, $markup);
		} else {
			// FIXME ???
			if($is_type_xhtml && !$is_markup_xhtml)
				if(!$document_charset) {
					phpQuery::debug("Full markup load (XML), appending charset '$charset'");
					$markup = $this->charsetAppendToXML($markup, $charset);
				}
			// see http://pl2.php.net/manual/en/book.dom.php#78929
			// LIBXML_DTDLOAD (>= PHP 5.1)
			// does XML ctalogues works with LIBXML_NONET
			//		$this->document->resolveExternals = true;
			// TODO test LIBXML_COMPACT for performance improvement
			// create document
			$this->documentCreate($charset);
			if(phpversion() < 5.1) {
				$this->document->resolveExternals = true;
				$return = @$this->document->loadXML($markup);
			} else {
				$libxml_static = LIBXML_DTDLOAD | LIBXML_DTDATTR | LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR;
				$return = $this->document->loadXML($markup, $libxml_static);
				// 				if(!$return)
				// 					$return = $this->document->loadHTML($markup);
			}
			if($return)
				$this->root = $this->document;
		}
		if($return) {
			if(!$this->contentType) {
				$this->contentType = ($this->isXHTML) ? 'application/xhtml+xml' : 'text/xml';
			}
			return $return;
		} else {
			throw new Exception(__LINE__.': '.__METHOD__." -> Error loading XML markup");
		}
	}

	protected function isXHTML($markup = null): bool
	{
		if(!isset($markup)) {
			return str_contains($this->contentType, 'xhtml');
		}
		// XXX ok ?
		return str_contains($markup, "<!DOCTYPE html");
		//		return stripos($doctype, 'xhtml') !== false;
		//		$doctype = isset($dom->doctype) && is_object($dom->doctype)
		//			? $dom->doctype->publicId
		//			: self::$defaultDoctype;
	}

	protected function isXML($markup): bool
	{
		//		return str_contains($markup, '<?xml')str_contains && stripos($markup, 'xhtml') === false;
		return str_contains(substr($markup, 0, 100), '<'.'?xml');
	}

	protected function contentTypeToArray($content_type): array
	{
		$matches = explode(';', trim(strtolower($content_type)));
		if(isset($matches[1])) {
			$matches[1] = explode('=', $matches[1]);
			// strip 'charset='
			$matches[1] = isset($matches[1][1]) && trim($matches[1][1]) ? $matches[1][1] : $matches[1][0];
		} else
			$matches[1] = null;
		return $matches;
	}

	protected function contentTypeFromHTML($markup): array
	{
		$matches = [];
		// find meta tag
		preg_match('@<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', $markup, $matches);
		if(!isset($matches[0]))
			return [null, null];
		// get attr 'content'
		preg_match('@content\\s*=\\s*(["|\'])(.+?)\\1@', $matches[0], $matches);
		if(!isset($matches[0]))
			return [null, null];
		return $this->contentTypeToArray($matches[2]);
	}

	protected function charsetFromHTML($markup)
	{
		$content_type = $this->contentTypeFromHTML($markup);
		return $content_type[1];
	}

	protected function charsetFromXML($markup): string|bool
	{
		$matches = [];
		// find declaration
		preg_match('@<'.'?xml[^>]+encoding\\s*=\\s*(["|\'])(.*?)\\1@i', $markup, $matches);
		return isset($matches[2]) ? strtolower($matches[2]) : false;
	}

	protected function charsetFixHTML($markup): string
	{
		$matches = [];
		// find meta tag
		preg_match('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', $markup, $matches, PREG_OFFSET_CAPTURE);
		if(!isset($matches[0]))
			return '';
		$meta_content_type = $matches[0][0];
		$markup = substr($markup, 0, $matches[0][1]).substr($markup, $matches[0][1] + strlen($meta_content_type));
		$head_start = stripos($markup, /** @lang text */ '<head>');
		return substr($markup, 0, $head_start + 6).$meta_content_type.substr($markup, $head_start + 6);
	}

	protected function charsetAppendToHTML(string $html, string $charset, bool $xhtml = false): array|string|null
	{
		// remove existing meta[type=content-type]
		$html = preg_replace('@\s*<meta[^>]+http-equiv\\s*=\\s*(["|\'])Content-Type\\1([^>]+?)>@i', '', $html);
		$meta = '<meta http-equiv="Content-Type" content="text/html;charset='.$charset.'" '.($xhtml ? '/' : '').'>';
		if(!str_contains($html, '<head')) {
			if(!str_contains($html, '<html')) {
				return $meta.$html;
			} else {
				return preg_replace('@<html(.*?)(?(?<!\?)>)@s', /** @lang text */ "<html\\1><head>$meta</head>", $html);
			}
		} else {
			return preg_replace('@<head(.*?)(?(?<!\?)>)@s', '<head\\1>'.$meta, $html);
		}
	}

	protected function charsetAppendToXML($markup, $charset): string
	{
		$declaration = '<'.'?xml version="1.0" encoding="'.$charset.'"?'.'>';
		return $declaration.$markup;
	}

	public static function isDocFragmentHTML($markup): bool
	{
		return stripos($markup, '<html') === false && stripos($markup, '<!doctype') === false;
	}

	public static function isDocFragmentXML($markup): bool
	{
		return stripos($markup, '<'.'?xml') === false;
	}

	public static function isDocFragmentXHTML($markup): bool
	{
		return self::isDocFragmentHTML($markup);
	}

	/**
	 * @noinspection PhpUnused
	 */
	public function importAttr($value)
	{
	}

	/**
	 * @throws Exception
	 */
	public function import($source, $source_charset = null): array
	{
		// TODO charset conversions
		$return = [];
		if($source instanceof DOMNode)
			$source = [$source];
		if(is_array($source) || $source instanceof DOMNodeList) {
			// dom nodes
			self::debug('Importing nodes to document');
			foreach($source as $node)
				$return[] = $this->document->importNode($node, true);
		} else {
			// string markup
			$fake = $this->docFragmentCreate($source, $source_charset);
			if($fake === false) {
				throw new Exception(__LINE__.': '.__METHOD__.' -> Error loading documentFragment markup');
			} else {
				$return = $fake->import($fake->root->childNodes);
			}
		}
		return $return;
	}

	/**
	 * @throws \Exception
	 */
	protected function docFragmentCreate($source, $charset = null): bool|DOMDocWrapper
	{
		$fake = new DOMDocWrapper();
		$fake->contentType = $this->contentType;
		$fake->isXML = $this->isXML;
		$fake->isHTML = $this->isHTML;
		$fake->isXHTML = $this->isXHTML;
		$fake->root = $fake->document;
		if(!$charset)
			$charset = $this->charset;
		//	$fake->documentCreate($this->charset);
		if($source instanceof DOMNode)
			$source = [$source];
		if(is_array($source) || $source instanceof DOMNodeList) {
			// dom nodes
			// load fake document
			if(!$this->docFragLoadMarkup($fake, $charset))
				return false;
			$nodes = $fake->import($source);
			foreach($nodes as $node)
				$fake->root->appendChild($node);
		} else {
			// string markup
			$this->docFragLoadMarkup($fake, $charset, $source);
		}
		return $fake;
	}

	private function docFragLoadMarkup($fragment, string $charset, string $markup = ''): bool
	{
		// tempolary turn off
		$fragment->isDocumentFragment = false;
		if($fragment->isXML) {
			if($fragment->isXHTML) {
				$fake = ['<fa', 'ke xmlns="http://www.w3.org/1999/xhtml">', $markup, '</fa', 'ke>'];
				// add FAKE element to set default namespace
				$fragment->loadMarkupXML('<?xml version="1.0" encoding="'.$charset.'"?>'.'<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" '.'"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'.implode('', $fake));
				$fragment->root = $fragment->document->firstChild->nextSibling;
			} else {
				$fragment->loadMarkupXML('<?xml version="1.0" encoding="'.$charset.'"?><fake>'.$markup.'</fake>');
				$fragment->root = $fragment->document->firstChild;
			}
		} else {
			$markup2 = /** @lang text */
				phpQuery::$defaultDoctype.'<html><head><meta http-equiv="Content-Type" content="text/html;charset='.$charset.'"></head>';
			$no_body = !str_contains($markup, '<body');
			if($no_body)
				$markup2 .= '<body>';
			$markup2 .= $markup;
			if($no_body)
				$markup2 .= '</body>';
			$markup2 .= '</html>';
			$fragment->loadMarkupHTML($markup2);
			$fragment->root = $fragment->document->firstChild->nextSibling->firstChild->nextSibling;
		}
		if(!$fragment->root)
			return false;
		$fragment->isDocumentFragment = true;
		return true;
	}

	protected function docFragToMarkup($fragment): string
	{
		phpQuery::debug('docFragToMarkup');
		$tmp = $fragment->isDocumentFragment;
		$fragment->isDocumentFragment = false;
		$markup = $fragment->markup();
		if($fragment->isXML) {
			$markup = substr($markup, 0, strrpos($markup, '</fake>'));
			if($fragment->isXHTML) {
				$markup = substr($markup, strpos($markup, '<fake') + 43);
			} else {
				$markup = substr($markup, strpos($markup, '<fake>') + 6);
			}
		} else {
			$markup = substr($markup, strpos($markup, '<body>') + 6);
			$markup = substr($markup, 0, strrpos($markup, '</body>'));
		}
		$fragment->isDocumentFragment = $tmp;
		if(phpQuery::$debug)
			phpQuery::debug('docFragToMarkup: '.substr($markup, 0, 150));
		return $markup;
	}

	/**
	 * @throws \Exception
	 */
	public function markup($nodes = null, $inner_markup = false): false|array|string
	{
		if(isset($nodes) && count($nodes) == 1 && $nodes[0] instanceof DOMDocument)
			$nodes = null;
		if(isset($nodes)) {
			$markup = '';
			if(!is_array($nodes) && !($nodes instanceof DOMNodeList))
				$nodes = [$nodes];
			if($this->isDocumentFragment && !$inner_markup)
				foreach($nodes as $key => $node)
					if($node->isSameNode($this->root)) {
						//	var_dump($node);
						$nodes = array_slice($nodes, 0, $key) + phpQuery::domNodeListToArray($node->childNodes) + array_slice($nodes, $key + 1);
					}
			if($this->isXML && !$inner_markup) {
				self::debug('Getting outerXML with charset '.$this->charset);
				// we need outerXML, so we can benefit from
				// $node param support in saveXML()
				foreach($nodes as $node)
					$markup .= $this->document->saveXML($node);
			} else {
				$loop = [];
				if($inner_markup) {
					foreach($nodes as $node) {
						if($node->childNodes) {
							foreach($node->childNodes as $child) {
								$loop[] = $child;
							}
						} else {
							$loop[] = $node;
						}
					}
				} else {
					$loop = $nodes;
				}

				self::debug('Getting markup, moving selected nodes ('.count($loop).') to new DocumentFragment');
				$fake = $this->docFragmentCreate($loop);
				$markup = $this->docFragToMarkup($fake);
			}
			if($this->isXHTML) {
				self::debug('Fixing XHTML');
				$markup = self::markupFixXHTML($markup);
			}
			self::debug('Markup: '.substr($markup, 0, 250));
		} elseif($this->isDocumentFragment) {
			// documentFragment, html only...
			self::debug('Getting markup, DocumentFragment detected');
			//				return $this->markup(
			////					$this->document->getElementsByTagName('body')->item(0)
			//					$this->document->root, true
			//				);
			$markup = $this->docFragToMarkup($this);
			// no need for markupFixXHTML, as it's done thought markup($nodes) method
		} else {
			self::debug('Getting markup ('.($this->isXML ? 'XML' : 'HTML').'), final with charset '.$this->charset);
			$markup = $this->isXML ? $this->document->saveXML() : $this->document->saveHTML();
			if($this->isXHTML) {
				self::debug('Fixing XHTML');
				$markup = self::markupFixXHTML($markup);
			}
			self::debug('Markup: '.substr($markup, 0, 250));

		}
		return $markup;
	}

	/**
	 * @param $markup
	 * @return array|string
	 */
	protected static function markupFixXHTML($markup): array|string
	{
		$markup = self::expandEmptyTag('script', $markup);
		$markup = self::expandEmptyTag('select', $markup);
		return self::expandEmptyTag('textarea', $markup);
	}

	public static function debug($text): void
	{
		phpQuery::debug($text);
	}

	public static function expandEmptyTag($tag, $xml): array|string
	{
		$indice = 0;
		while($indice < strlen($xml)) {
			$pos = strpos($xml, "<$tag ", $indice);
			if($pos) {
				$pos_cierre = strpos($xml, ">", $pos);
				if($xml[$pos_cierre - 1] == '/') {
					$xml = substr_replace($xml, "></$tag>", $pos_cierre - 1, 2);
				}
				$indice = $pos_cierre;
			} else {
				break;
			}
		}
		return $xml;
	}
}