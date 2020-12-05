<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp;


class GoogleNlSyntaxParser
{
	/**
	 * Logger
	 *
	 * @var \Psr\Log\LoggerInterface
	 */
	private $_logger;

	public function __construct( \Psr\Log\LoggerInterface $logger)
    {
		$this->_logger	=	$logger;
    }

    /**
     * @param array $data
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken[]
     */
    public function parseGoogleResponse( $data)
    {
    	/** @var \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $root */
    	/** @var \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $last_token */
    	
    	$roots		=	[];
    	$last_token	=	null;
    	
    	$all_by_index		=	array();
    	$waiting_for_index	=	array();
    	
    	$index 	= 	0;
    	foreach ( $data['sentences'] as $sentence_data) 
    	{
    		$content  			= 	$sentence_data['text']['content'];
    		$sentence_begin 	= 	$sentence_data['text']['beginOffset'];
    		$sentence_end 		= 	$sentence_begin + strlen( $content) - 1;
    	
    		$this->_logger->debug( '-- Handling sentence ['.$sentence_begin.']['.$sentence_end.']['.$content.']');
    		while ( $index < count( $data['tokens']) && $data['tokens'][$index]['text']['beginOffset'] <= $sentence_end)
    		{
    			$token_data		=	$data['tokens'][$index];
    			$token_index	=	count( $all_by_index);
    			
//     			$this->_logger->debug( '---- Got token ['.$index.']['.$token_data['text']['content'].']');
    			
    			$token			=	new \Convo\Pckg\Gnlp\GoogleNlSyntaxToken( $token_index, $this->_logger);
				$token->setData( $token_data);
				$token->setSentence( $content);
    			
    			if ( $last_token) {
    				$last_token->setNext( $token);
    				$token->setPrevious( $last_token);
    			}
    			
//     			$this->_logger->debug( 'Created token ['.$token_index.']['.$token->getLabel().']['.$token->isRoot().']');
    			
    			$all_by_index[]	=	$token;
    			
    			if ( !$token->isRoot()) {
    				if ( isset( $all_by_index[$token->getHeadTokenIndex()])) {
//     					$this->_logger->debug( 'Adding to parent');
    					/* @var $parent \Convo\Pckg\Gnlp\GoogleNlSyntaxToken */
    					$parent	=	$all_by_index[$token->getHeadTokenIndex()];
    					$parent->addChild( $token);
    				} else {
    					if ( !isset( $waiting_for_index[$token->getHeadTokenIndex()])) {
//     						$this->_logger->debug( 'Creating waiting quee for ['.$token->getHeadTokenIndex().']');
    						$waiting_for_index[strval( $token->getHeadTokenIndex())]	=	array();
    					}
//     					$this->_logger->debug( 'Waiting for ['.$token->getHeadTokenIndex().']');
    					$waiting_for_index[strval( $token->getHeadTokenIndex())][]	=	$token;
    				}
    			} else {
//     				$this->_logger->debug( 'Found root');
    				$roots[] = $token;
    			}
    			
    			
    			//     			$this->_logger->debug( 'Got token ['.$index.']['.print_r( $token_data, true).']');
	    		$index ++;
	    		$last_token	=	$token;
    		}
    	}
    	
    	if ( empty($roots)) {
    		throw new \Exception( 'Could not find even single root in data');
    	}
    	
    	foreach ( $waiting_for_index as $key => $val) {
    		$parent	=	$all_by_index[intval( $key)];
    		foreach ( $val as $child) {
    			$parent->addChild( $child);
    		}
    	}
    	
    	return $roots;
    }
    
    // UTIL
    public function __toString()
    {
    	return get_class( $this).'';
    }
}