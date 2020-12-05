<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp;

abstract class GoogleNlSyntaxTokenUtils
{
	
    public static function getTokensContent( $tokens, $separator=' ') {
    	$all	=	array();
    	foreach ( $tokens as $token) {
    		/* @var $token \Convo\Pckg\Gnlp\GoogleNlSyntaxToken */
    		$all[]	=	$token->getContent();
    	}
    	return implode( $separator, $all);
    }
	
    public static function sortTokens( $tokens) {
    	// usort( $all, array( '\Convo\Pckg\Gnlp\GoogleNlSyntaxTokenUtils', 'compareTokensByIndex'));
    	$data	=	array_merge( $tokens);
    	usort( $data, array( '\Convo\Pckg\Gnlp\GoogleNlSyntaxTokenUtils', 'compareTokensByIndex'));
    	return $data;
    }
    
    public static function compareTokensByIndex( \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $a, \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $b)
    {
    	if ($a->getIndex() == $b->getIndex()) {
    		return 0;
    	}
    	return ($a->getIndex() < $b->getIndex()) ? -1 : 1;
    }
    
}