<?php
declare(strict_types = 1);
/***
 * Date 25.04.2023
 * @author zeroc0de <98693638+zeroc0de2022@users.noreply.github.com>
 */


namespace Cpsync\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AssetExtension
 */
class AssetExtension extends AbstractExtension
{
	/**
	 * Server params of request
	 * @var array $serverParams
	 */
	private array $serverParams;

	/**
	 * Factory Twig functions
	 * @var TwigFuncFactory
	 */
	private TwigFuncFactory $twigFuncFactory;

	/**
	 * AssetExtension constructor.
	 * @param array $serverParams - Server params of request
	 * @param TwigFuncFactory $twigFuncFactory
	 */
	public function __construct(array $serverParams, TwigFuncFactory $twigFuncFactory)
	{
		$this->serverParams = $serverParams;
		$this->twigFuncFactory = $twigFuncFactory;
	}

	/**
	 * Returns a list of extension functions
	 * @return array|TwigFunction[] - the list of Twig extension functions for use in Twig templates
	 */
	public function getFunctions(): array
	{
		return [
			$this->twigFuncFactory->create('asset_url', [$this, 'getAssetUrl']),
			$this->twigFuncFactory->create('base_url', [$this, 'getBaseUrl']),
			$this->twigFuncFactory->create('get_uri', [$this, 'getUri']),
			$this->twigFuncFactory->create('get_full_uri', [$this, 'getFullUri'])
		];
	}

	/**
	 * Returns the URL for the resource
	 * @param string $path - path to resource like /path
	 * @param string $endpath - additional path to the resource like /endpath.ext
	 * @return string - URL for the resource like http://example.com/path/endpath.ext
	 */
	public function getAssetUrl(string $path, string $endpath = ''): string
	{
		return $this->getBaseUrl().$path.$endpath;
	}

	/**
	 * Returns the base URL for the resource
	 * @return string - the base URL for the resource (protocol + host) like http://example.com/
	 */
	public function getBaseUrl(): string
	{
		$scheme = $this->serverParams['REQUEST_SCHEME'] ?? 'http';
		return $scheme.'://'.$this->serverParams['HTTP_HOST'].'/';
	}


	/**
	 * Returns URI for the resource
	 * @return string
	 */
	public function getUri(): string
	{
		return trim($this->serverParams['REQUEST_URI'], '/');
	}


	/**
	 * Returns the full URI for the resource
	 * @param string $path
	 * @return string
	 */
	public function getFullUri(string $path): string
	{
		return $this->getBaseUrl().$this->getUri().'/'.$path;
	}

}