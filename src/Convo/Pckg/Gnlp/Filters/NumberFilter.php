<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp\Filters;

class NumberFilter extends AbstractFilter
{
	
	public static $DFAULTS	=	array(
// 			'length' => 4,
			'group' => false,
			'exclusive' => false,
// 			'slot_name' => 'pin'
	);
	
	private $_length;
	private $_group;
	private $_exclusive;
	private $_slotName;
	
	public function __construct( $config)
	{
		parent::__construct();
		
		$config	=	array_merge( self::$DFAULTS, $config);
		
		if ( isset( $config['length'])) {
			$this->_length		=	$config['length'];
		}
		
		if ( isset( $config['slot_name'])) {
			$this->_slotName	=	$config['slot_name'];
		}
		
		$this->_group		=	$config['group'];
		$this->_exclusive	=	$config['exclusive'];
	}
	
	public function visitToken( \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $token)
	{
		if ( !$token->isNumber()) {
			$this->_logger->debug( 'Token ['.$token.'] is not number. Skipping ...');
			return ;
		}
		
		if ( $this->_group) {
			$this->_logger->debug( 'Going to check for number group');
			/*  @var $tokens \Convo\Pckg\Gnlp\GoogleNlSyntaxToken */
			$token	=	$token->getNumberGroup();
			$this->_logger->debug( 'Got group token ['.$token.']');
		}
		
		$all_content	=	str_replace( ' ', '', $token->getRoot()->getAllContent());
		
		$this->_logger->debug( 'Visiting token ['.$token.'] - token ['.$token->getContent().'] all ['.$all_content.']');
		
		if ( $this->_exclusive && $token->getContent() != $all_content) {
			$this->_logger->debug( 'Token ['.$token.'] is not exclusive content, but it is expected. Skipping ...');
			return ;
		}
		
		$value	=	$token->getContent();
		
		if ( isset( $this->_length) && strlen( $value) != $this->_length && $this->_length != 0) {
			$this->_logger->debug( 'Value ['.$value.'] is not expected length ['.$this->_length.']');
			return ;
		} 
		
		
		// ALL OK
		
		$this->_filterResult->addToken( $token);
		
		if ( $this->_slotName) {
			$this->_logger->debug( 'Setting the slot ['.$this->_slotName.'] value ['.$value.']');
			$this->_filterResult->setSlotValue( $this->_slotName, $value);
		}
	}
	
	// UTIL
// 	public function __toString()
// 	{
// 		return get_class( $this).'[]';
// 	}
}