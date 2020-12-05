<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Filters;

class PriceRangeFilter extends AbstractFilter
{
	public static $DFAULTS	=	array(
			'slot_name_min' => 'min_price',
			'slot_name_max' => 'max_price',
			'min_value' => 1000
	);

	const ONLY_NUM_REGEX    =   "/\D/miu";

	private $_slotNameMin;
	private $_slotNameMax;
	private $_minValue;
	
	public function __construct( $config=array())
	{
		parent::__construct();
		
		$config	=	array_merge( self::$DFAULTS, $config);
		
		$this->_slotNameMin	=	$config['slot_name_min'];
		$this->_slotNameMax	=	$config['slot_name_max'];
		$this->_minValue	=	$config['min_value'];
	}
	
	public function visitToken( \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $token)
	{
	    if ( strtolower( $token->getContent()) == 'from' || strtolower( $token->getContent() == 'between')) {
            $next       =   $token->getNext();
            $min_val    =   preg_replace( self::ONLY_NUM_REGEX, '', $next->getContent());

            if ( intval( $min_val) < 1000) $min_val = (string) intval( $min_val) * 1000;

            $this->_filterResult->setSlotValue( $this->_slotNameMin, $min_val);
            $this->_filterResult->addToken( $next);

            if ( $next->getNext() !== null) {
                $curr       =   $next->getNext();
                $max_val    =   null;

                while ( $curr->isNumber() === false) {
                    if ( $curr->getNext() === null) {
                        break;
                    }

                    if ( $curr->getNext()->isNumber()) {
                        $max_val    =   preg_replace( self::ONLY_NUM_REGEX, '', $curr->getNext()->getContent());
                        $max_token  =   $curr->getNext();

                        $this->_filterResult->setSlotValue( $this->_slotNameMax, $max_val);
                        $this->_filterResult->addToken( $max_token);

                        break;
                    }

                    $curr   =   $curr->getNext();
                }
            }

		    return ;
        }
		
		if ( !$token->isNumber()) {
			$this->_logger->debug( 'Token ['.$token.'] is not numeric. Skipping ...');
			return ;
		}
		
		$value	=	intval( preg_replace( self::ONLY_NUM_REGEX, '', $token->getContent()));
		
		$this->_logger->debug( 'Got value ['.$value.'] for token ['.$token.']');
		
		if ( $this->_minValue && $value < $this->_minValue) {
			$this->_logger->debug( 'Value ['.$value.'] is lover than min allowed ['.$this->_minValue.']. Skipping ...');
			return ;
		}
		
		// UP TO MATCH
		if ( $token->getIndex() > 1) {
			// enough room on left for "up to"
			$prev		=	$token->getPrevious();
			$prev_prev	=	$prev->getPrevious();
			
			$up_to	=	strtolower( \Convo\Pckg\Gnlp\GoogleNlSyntaxTokenUtils::getTokensContent( array( $prev_prev, $prev)));
			
			$this->_logger->debug( 'Got up to test ['.$up_to.']');
			
			if ( $up_to == 'up to') {
				$this->_filterResult->addToken( $token);
				$this->_filterResult->setSlotValue( $this->_slotNameMax, $value);
				return ;
			}
		}

		return;
	}
	
	// UTIL
	public function __toString()
	{
		return get_class( $this).'[]';
	}

	private function _stripDollarSign( $string)
    {
        $clean  =   str_replace( '$', '', $string);

        return $clean;
    }
}