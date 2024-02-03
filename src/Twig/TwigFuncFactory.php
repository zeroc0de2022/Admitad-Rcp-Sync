<?php
declare(strict_types = 1);

namespace Cpsync\Twig;

use Twig\TwigFunction;

/**
 * Class TwigFuncFactory
 */
class TwigFuncFactory
{
	/**
	 * Create a new TwigFunction instance.
	 * This is a wrapper for the TwigFunction constructor.
	 * @param ...$arguments
	 * @return TwigFunction
	 */
	public function create(...$arguments): TwigFunction
	{
		return new TwigFunction(...$arguments);
	}
}