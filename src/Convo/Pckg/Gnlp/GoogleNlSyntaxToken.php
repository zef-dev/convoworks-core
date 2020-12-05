<?php declare(strict_types=1);

namespace Convo\Pckg\Gnlp;

/*
{
            "text": {
                "content": "is",
                "beginOffset": 4
            },
            "partOfSpeech": {
                "tag": "VERB",
                "aspect": "ASPECT_UNKNOWN",
                "case": "CASE_UNKNOWN",
                "form": "FORM_UNKNOWN",
                "gender": "GENDER_UNKNOWN",
                "mood": "INDICATIVE",
                "number": "SINGULAR",
                "person": "THIRD",
                "proper": "PROPER_UNKNOWN",
                "reciprocity": "RECIPROCITY_UNKNOWN",
                "tense": "PRESENT",
                "voice": "VOICE_UNKNOWN"
            },
            "dependencyEdge": {
                "headTokenIndex": 1,
                "label": "ROOT"
            },
            "lemma": "be"
        }
 * */

class GoogleNlSyntaxToken
{
	/**
	 * @var \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
	 */
	private $_parent;
	private $_childrenLeft	=	array();
	private $_childrenRight	=	array();
	
	/**
	 * @var \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
	 */
	private $_source;
	
	/**
	 * @var \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
	 */
	private $_previous;
	
	/**
	 * @var \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
	 */
	private $_next;

    /**
     * @var string
     */
    private $_sentence;
	private $_data;
	private $_index;

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    public function __construct( $index, \Psr\Log\LoggerInterface$logger)
    {
        $this->_index	=	$index;
        $this->_logger	=	$logger;
    }
    
    public function setData( $data) {
    	$this->_data	=	$data;
    }

    public function setSentence( $sentence) {
        $this->_sentence = $sentence;
    }
    
    public function getSentence() {
        return $this->_sentence;
    }

    public function getIndex() {
    	return $this->_index;
    }
    
    public function equals( \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $token) {
        return $token->_data == $this->_data;
    }

    public function getDistanceToRoot()
    {
    	if ( $this->isRoot()) {
    		return 0;
    	}
    	
    	return 1 + $this->getParent()->getDistanceToRoot();
    }

    public function getPathToRoot()
    {
        if ($this->isRoot()) {
            return [$this];
        }

        return array_merge($this->getParent()->getPathToRoot(), [$this]);
    }
    
    public function getDistance( GoogleNlSyntaxToken $token)
    {
        if ($this->isRoot()) {
            return $token->getDistanceToRoot();
        }

        if ($token->isRoot()) {
            return $this->getDistanceToRoot();
        }

        if ($this->isTokenInPath($token)) {
            return $this->getDistanceToRoot() - $token->getDistanceToRoot();
        }

        if ($token->isTokenInPath($this)) {
            return $token->getDistanceToRoot() - $this->getDistanceToRoot();
        }

        if ($this->getParent()->equals($token->getParent())) {
            return 2;
        }

        return $token->getDistanceToRoot() + $this->getDistanceToRoot();
    }

    public function isTokenInPath(GoogleNlSyntaxToken $token)
    {
        $path = $this->getPathToRoot();

        foreach ($path as $path_token) {
            if ($path_token->equals($token)) {
                return true;
            }
        }

        return false;
    }

    public function getTag() {
    	// PRON, VERB, NOUN, PRT, CONJ, ...
    	return strtolower( $this->_data['partOfSpeech']['tag']);
    }

    public function isProper() {
        return $this->_data['partOfSpeech']['proper'] === 'PROPER'; 
    }

    public function isNumber() {
    	$value	=	$this->_data['text']['content'];
    	$ret	=	is_numeric( $value) || strtolower( $this->_data['partOfSpeech']['tag']) == 'num';
    	
    	if ( !$ret) {
    		// QUICKFIX FOR ORDINALS
    		$value		= 	preg_replace('/\\b(\d+)(?:st|nd|rd|th)\\b/', '$1', $value);
    		if ( is_numeric( $value)) {
    			$this->_data['text']['content']	=	$value;
    			return true;
    		}
    	}
    	return $ret;
    }
    
    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxTokenGroup
     */
    public function getNumberGroup() {
    	$group	=	new \Convo\Pckg\Gnlp\GoogleNlSyntaxTokenGroup( $this->_logger);
    	
    	if ( $this->isNumber()) {
    		
    		$group->addToken( $this);
    		
    		foreach ( $this->getChildren() as $child) {
    			$this->_logger->debug( 'Handling child ['.$child.']');
    			/** @var \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $child */
    			$child_group = $child->getNumberGroup();
    			$group->addTokens($child_group->getTokens());
    		}
    	} else {
    		$this->_logger->debug( 'Token ['.$this.'] is not number. Exiting ...');
    	}
    	
    	return $group;
    }
    
    public function getLabel() {
    	// discourse, nsubj, root, dobj, aux, vmod, tmod
    	return strtolower( $this->_data['dependencyEdge']['label']);
    }
    
    public function getHeadTokenIndex() {
    	return $this->_data['dependencyEdge']['headTokenIndex'];
    }
    
    public function isRoot() {
    	return strtolower( $this->getLabel()) == 'root';
    }
    
    public function getLemma() {
    	//  "lemma": "be"
    	if ( isset( $this->_data['lemma'])) {
    		return strtolower( $this->_data['lemma']);
    	}
    }

    public function getLemaContent() {
    	//  "lemma": "be"
    	if ( isset( $this->_data['lemma'])) {
    		return strtolower( $this->_data['lemma']);
    	}
    	return $this->getContent();
    }
    
    public function getContent() {
    	$content    =   $this->_data['text']['content'];

    	if ( $this->isNumber() && strpos( $content, ',') !== false)
    	    $content    =   str_replace( ',', '', $content);

    	return $content;
    }
    
    public function getAllContent() {
    	$all	=	\Convo\Pckg\Gnlp\GoogleNlSyntaxTokenUtils::sortTokens( $this->getAll());
    	$str	=	array();

    	foreach ( $all as $token) {
    		$str[]	=	$token->getContent();
    	}
    	return implode( ' ', $str);
    }

    // NODE INTERFACE
    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
     */
    public function getParent() {
    	if ( isset( $this->_parent)) {
    		return $this->_parent;
    	}
    	return $this;
    }
    
    /**
     * @param \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $parent
     */
    public function setParent( $parent) {
    	$this->_parent	=	$parent;
    }
    
    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
     */
    public function getNext() {
    	return $this->_next;
    }
    
    /**
     * @param \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $next
     */
    public function setNext( $next) {
    	$this->_next		=	$next;
    }
    
    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
     */
    public function getPrevious() {
    	return $this->_previous;
    }
    
    /**
     * @param \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $previous
     */
    public function setPrevious( $previous) {
    	$this->_previous	=	$previous;
    }
    
    /**
     * @param \Convo\Pckg\Gnlp\GoogleNlSyntaxToken $child
     */
    public function addChild( $child) {
    	
    	if ( $child->getIndex() < $this->getIndex()) {
    		$this->_childrenLeft[]	=	$child;
    	} else {
    		$this->_childrenRight[]	=	$child;
    	}
    	
    	$child->setParent( $this);
    }

    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken[]
     */
    public function getChildren()
    {
    	return array_merge( $this->_childrenLeft, $this->_childrenRight);
    }

    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
     */
    public function getRoot() {
    	if ( isset( $this->_parent)) {
    		return $this->_parent->getRoot();
    	}
    	
    	if ( $this->isRoot()) {
    		return $this;
    	}
    	
    	throw new \Exception( 'No root found in ['.$this.']');
    }

    /**
     * @return \Convo\Pckg\Gnlp\GoogleNlSyntaxToken
     */
    public function getSource() {
    	return $this->_source;
    }

    // FIND
    /**
     * @param \Convo\Pckg\Gnlp\Filters\ITextFilter $filter
     */
    public function collectInto( \Convo\Pckg\Gnlp\Filters\ITextFilter $filter)
    {
    	$filter->visitToken( $this);

    	foreach ( $this->getChildren() as $child) {
    		/* @var $child \Convo\Pckg\Gnlp\GoogleNlSyntaxToken */
    		$child->collectInto( $filter);
    	}
    }

    public function getAll() {
    	$all	=	array();
    	$all[]	=	$this;
    	foreach ( $this->getChildren() as $child) {
    		$all	=	array_merge( $all, $child->getAll());
    	}
    	return $all;
    }


    // UTIL

    public function __toString()
    {
    	$str	=	'';
    	if ( isset( $this->_data['partOfSpeech']['tag'])) {
    		$str	.=	'['.$this->_data['partOfSpeech']['tag'].']';
    	}
    	if ( isset( $this->_data['partOfSpeech']['proper'])) {
    		$str	.=	'['.$this->_data['partOfSpeech']['proper'].']';
    	}
    	if ( isset( $this->_data['text']['content'])) {
    		$str	.=	'['.$this->_data['text']['content'].']';
    	}
    	if ( isset( $this->_data['lemma'])) {
    		$str	.=	'['.$this->_data['lemma'].']';
    	}

    	return get_class( $this).'['.$this->_index.']'.$str;
    }
}