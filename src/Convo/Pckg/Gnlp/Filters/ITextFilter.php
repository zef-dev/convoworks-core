<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Filters;

interface ITextFilter
{
	/**
	 * @param \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $token
	 */
	public function visitToken( \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $token);
	
	/**
	 * @return \Convo\Pckg\Gnlp\NlpFilterResult
	 */
	public function getFilterResult();
}