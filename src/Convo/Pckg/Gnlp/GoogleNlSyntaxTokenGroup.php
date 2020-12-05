<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp;

class GoogleNlSyntaxTokenGroup extends \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
{
	private $_tokens	=	array();
	
	public function __construct( \Psr\Log\LoggerInterface $logger)
    {
    	parent::__construct( 0, $logger);
    }
    
    public function getTokens() {
    	return $this->_tokens;
    }
    
    public function addTokens( $tokens) {
    	foreach ( $tokens as $token) {
    		$this->addToken( $token);
    	}
    }
    
    public function addToken( $token) {
    	$this->_tokens[]	=	$token;
    }
    
    public function setData( $data) {
    	$this->_getLast()->setData( $data);
    }
    
    public function getIndex() {
    	return $this->_getLast()->getIndex();
    }
    
    public function getDistanceToRoot()
    {
    	return $this->_getLast()->getDistanceToRoot();
    }
    
    public function getTag() {
    	return $this->_getLast()->getTag();
    }
    
    public function isNumber() {
    	return $this->_getLast()->isNumber();
    }
    
    public function getNumberGroup() {
    	return $this;
    }
    
    public function getLabel() {
    	return $this->_getLast()->getLabel();
    }
    
    public function getHeadTokenIndex() {
    	return $this->_getLast()->getHeadTokenIndex();
    }
    
    public function isRoot() {
    	foreach ( $this->_tokens as $child) {
    		/* @var $child \Convo\Pckg\Gnlp\GoogleNlSyntaxToken */
    		if ( $child->isRoot()) {
    			return true;
    		}
    	}
    	return false;
    }
    
    public function getLemma() {
    	return $this->_getLast()->getLemma();
    }
    


    // NODE INTERFACE
    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
     */
    public function getParent() {
    	return $this->_getLast()->getParent();
    }
    
    /**
     * @param \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $parent
     */
    public function setParent( $parent) {
    	$this->_getLast()->setParent( $parent);
    }
    
    /**
     * @param \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $child
     */
    public function addChild( $child) {
    	$this->_getFirst()->addChild( $child);
    }
    
    public function getChildren()
    {
    	return $this->_getLast()->getChildren();
    }

    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
     */
    public function getRoot() {
    	return $this->_getLast()->getRoot();
    }

    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
     */
    public function getSource() {
    	return $this->_getLast()->getSource();
    }
    
    // FIND
    /**
     * @param \Convo\Pckg\Gnlp\Filters\ITextFilter $filter
     */
    public function collectInto( \Convo\Pckg\Gnlp\Filters\ITextFilter $filter)
    {
    	$this->_getLast()->collectInto( $filter);
    }
    
    public function getAll() {
    	return $this->_getLast()->getAll();
    }
    
    public function getContent() {
    	$content	=	array();
    	$tokens		=	\Convo\Pckg\Gnlp\GoogleNlSyntaxTokenUtils::sortTokens( $this->_tokens);
    	
    	foreach ( $tokens as $child) {
    		/* @var $child \Convo\Pckg\Gnlp\GoogleNlSyntaxToken */
    		$this->_logger->debug( 'Getting child content ['.$child.']['.$child->getContent().']');
    		$content[]	=	$child->getContent();
    	}
    	
    	return implode( '', $content);
    }
    
    
    
    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
     */
    private function _getFirst()
    {
    	if ( empty( $this->_tokens)) {
    		throw new \Exception( 'No tokens defined yet');
    	}
    	return $this->_tokens[0];
    }
    
    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
     */
    private function _getLast()
    {
    	if ( empty( $this->_tokens)) {
    		throw new \Exception( 'No tokens defined yet');
    	}
    	return $this->_tokens[( count( $this->_tokens) - 1)];
    }
    
    
    // UTIL
    public function __toString()
    {
    	$str	=	array();
    	foreach ( $this->_tokens as $token) {
    		$str[]	=	'['.$token.']';
    	}
    	return get_class( $this).'['.count( $this->_tokens).']['.implode( '', $str).']';
    }
}