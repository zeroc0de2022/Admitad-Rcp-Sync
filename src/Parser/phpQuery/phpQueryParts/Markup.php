<?php
declare(strict_types = 1);
/*
Date: 28.05.2023
Author: zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
*/

namespace Cpsync\Parser\phpQuery\phpQueryParts;

use Cpsync\Parser\phpQuery\phpQueryObject;

trait Markup
{


	/* @noinspection PhpUnused */
	public static function unsafePHPTags($content): array|string|null
	{
		return self::markupToPHP($content);
	}

	public static function phpToMarkup($php): array|string|null
	{
		$regexes = ['@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)<'.'?php?(.*?)(?:\\?>)([^\']*)\'@s', '@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)<'.'?php?(.*?)(?:\\?>)([^"]*)"@s',];
		foreach($regexes as $regex)
			while(preg_match($regex, $php, $matches)) {
				unset($matches);
				$php = preg_replace_callback($regex, ['phpQuery', 'phpToMarkupCallback'], $php);
			}
		$regex = '@(^|>[^<]*)+?(<\?php(.*?)(\?>))@s';
		//preg_match_all($regex, $php, $matches);
		//var_dump($matches);
		return preg_replace($regex, '\\1<php><!-- \\3 --></php>', $php);
	}

	/* @noinspection PhpUnused */
	public static function phpToMarkupCallback($mey, $charset = 'utf-8'): string
	{
		return $mey[1].$mey[2].htmlspecialchars('<'.'?php'.$mey[4].'?'.'>', ENT_QUOTES | ENT_NOQUOTES, $charset).$mey[5].$mey[2];
	}

	/* @noinspection PhpUnused */
	public static function markupToPHPCallback($mey): string
	{
		return '<'.'?php '.htmlspecialchars_decode($mey[1]).' ?'.'>';
	}

	public static function markupToPHP($content): array|string|null
	{
		if($content instanceof phpQueryObject)
			$content = $content->markupOuter();
		/* <php>...</php> to <?php...? > */
		$content = preg_replace_callback('@<php>\s*<!--(.*?)-->\s*</php>@s', ['phpQuery', 'markupToPHPCallback'], $content);
		/* <node attr='< ?php ? >'> extra space added to save highlighters */
		$regexes = ['@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(\')([^\']*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^\']*)\'@s', '@(<(?!\\?)(?:[^>]|\\?>)+\\w+\\s*=\\s*)(")([^"]*)(?:&lt;|%3C)\\?(?:php)?(.*?)(?:\\?(?:&gt;|%3E))([^"]*)"@s',];
		foreach($regexes as $regex)
			while(preg_match($regex, $content))
				$content = preg_replace_callback($regex, function($mey) {
					return $mey[1].$mey[2].$mey[3]."<?php ".str_replace(['%20', '%3E', '%09', '&#10;', '&#9;', '%7B', '%24', '%7D', '%22', '%5B', '%5D'], [' ', '>', '	', '\n', '	', '{', '$', '}', '"', '[', ']'], htmlspecialchars_decode($mey[4])." ?>".$mey[5].$mey[2]);
				}, $content);
		return $content;
	}

	public static function isMarkup($input): bool
	{
		return !is_array($input) && str_starts_with(trim($input), '<');
	}
}
