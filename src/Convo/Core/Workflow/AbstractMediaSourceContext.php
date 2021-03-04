<?php

namespace Convo\Core\Workflow;

use Convo\Core\DataItemNotFoundException;
use Convo\Core\Media\IAudioFile;

abstract class AbstractMediaSourceContext extends AbstractBasicComponent implements IMediaSourceContext
{
    const PARAM_NAME_QUERY_MODEL    =   'query_model';
    private $_id;
    
    private $_defaultLoop;
    private $_defaultShuffle;
    
    
    public function __construct( $properties)
    {
        parent::__construct( $properties);
        
        $this->_id              =   $properties['id'];
        
        $this->_defaultLoop     =   $properties['default_loop'];
        $this->_defaultShuffle  =   $properties['default_shuffle'];
    }
    
    
    /**
     * {@inheritDoc}
     * @see \Convo\Core\Workflow\IServiceContext::init()
     */
    public function init()
    {
    }
    
    
    /**
     * {@inheritDoc}
     * @see \Convo\Core\Workflow\AbstractBasicComponent::getId()
     */
    public function getId()
    {
        return $this->_id;
    }
    
    /**
     * @return IMediaSourceContext
     */
    public function getComponent()
    {
        return $this;
    }
    
    // READ SONGS
    /**
     * @return \Iterator
     */
    abstract public function getSongs();
    
    // MEDIA
    public function isEmpty() : bool
    {
        return empty( $this->getCount());
    }
    
    public function isLast() : bool
    {
        $model          =   $this->_getQueryModel();
        return $model['post_index'] >= $this->getCount() - 1;
    }
    
//     abstract public function getCount() : int;
//     {
//         // TODO  -???
//         return count( $this->getSongs());
//     }
    
    public function next() : IAudioFile
    {
        if ( $this->getCount() === 1 && $this->getLoopStatus()) {
            return $this->_getSong( 0);
        }
        
        if ( $this->isLast()) {
            if ( !$this->getLoopStatus()) {
                throw new DataItemNotFoundException( 'Can\'t get next. Loop is off and we are on the last result.');
            }
            return $this->_getSong( 0);
        }
        $model      =   $this->_getQueryModel();
        return $this->_getSong( $model['post_index'] + 1);
    }
    
    public function current() : IAudioFile {
        $model      =   $this->_getQueryModel();
        return $this->_getSong( $model['post_index']);
    }
    
    public function movePrevious() {
        $model      =   $this->_getQueryModel();
        $previous   =   $model['post_index'] - 1;
        if ( $previous < 0) {
            if ( !$model['loop_status']) {
                throw new DataItemNotFoundException( 'Can\'t move previous. Already at last first result');
            }
            $previous   =   $this->getCount() - 1;
        }
        $model['post_index'] = $previous;
        $this->_saveQueryModel( $model);
    }
    
    public function moveNext() {
        $model  =   $this->_getQueryModel();
        $next   =   $model['post_index'] + 1;
        if ( $next > $this->getCount() - 1) {
            if ( !$model['loop_status']) {
                throw new DataItemNotFoundException( 'Can\'t move next. Already at last result ['.$this->getCount().']');
            }
            $next   =   0;
        }
        $model['post_index'] = $next;
        $this->_saveQueryModel( $model);
    }
    
    public function seek( $index) {
        
        if ( $index > $this->getCount() - 1) {
            throw new DataItemNotFoundException( 'Can\'t move to the ['.$index.']. There are only ['.$this->getCount().'] songs');
        }
        if ( $index < 0) {
            throw new DataItemNotFoundException( 'Can\'t move to the ['.$index.'].');
        }
        
        $model  =   $this->_getQueryModel();
        $model['post_index'] = $index;
        $this->_saveQueryModel( $model);
    }
    
    public function rewind() {
        $model  =   $this->_getQueryModel();
        $model['post_index'] = 0;
        $this->_saveQueryModel( $model);
    }
    
    public function getOffset() : int {
        $model  =   $this->_getQueryModel();
        return $model['song_offset'];
    }
    public function setOffset( $offset) {
        $model  =   $this->_getQueryModel();
        $model['song_offset'] = $offset;
        $this->_saveQueryModel( $model);
    }
    
    public function setStopped( $offset=-1) {
        $model  =   $this->_getQueryModel();
        if ( $offset >= 0) {
            $model['song_offset'] = $offset;
        }
        $model['playing'] = false;
        $this->_saveQueryModel( $model);
    }
    
    public function setPlaying()
    {
        $model  =   $this->_getQueryModel();
        $model['playing'] = true;
        $this->_saveQueryModel( $model);
    }
    
    public function setLoopStatus( $loopStatus) {
        $model  =   $this->_getQueryModel();
        $model['loop_status'] = $loopStatus;
        $this->_saveQueryModel( $model);
    }
    public function getLoopStatus() : bool {
        $model  =   $this->_getQueryModel();
        return $model['loop_status'];
    }
    
    public function setShuffleStatus( $shuffleStatus) {
        $model  =   $this->_getQueryModel();
        $model['shuffle_status'] = $shuffleStatus;
        if ( $shuffleStatus) {
            $this->_logger->info( 'Reseting post index and shuffling playlist');
            $model['post_index'] = 0;
            shuffle( $model['playlist']);
        } else {
            $real_index             =   $model['playlist'][$model['post_index']];
            $this->_logger->info( 'Using real post index ['.$real_index.']');
            $model['post_index']    =   $real_index;
            sort( $model['playlist']);
        }
        $this->_saveQueryModel( $model);
    }
    public function getShuffleStatus() : bool {
        $model  =   $this->_getQueryModel();
        return $model['shuffle_status'];
    }
    
    
    // INFO
    public function getMediaInfo() : array
    {
        $info   =   IMediaSourceContext::DEFAULT_MEDIA_INFO;
        
        // has to be before _getQueryModel() is called
        if ( !$this->isEmpty()) {
            $info['current'] = $this->current();
            try {
                $info['next'] = $this->next();
            } catch ( DataItemNotFoundException $e) {
                $this->_logger->debug( $e->getMessage());
            }
        }
        
        $model  =   $this->_getQueryModel();
        
        $info   =   array_merge( $info, [
            'count' => $this->getCount(),
            'last' => $this->isLast(),
            'first' => $model['post_index'] === 0,
            'song_no' => $model['post_index'] + 1,
            'loop_status' => $model['loop_status'],
            'shuffle_status' => $model['shuffle_status'],
            'playing' => $model['playing'],
        ]);
        
        $this->_logger->debug( 'Got current media info ['.print_r( $info, true).']');
        
        return $info;
    }
    
    
    // QUERY
    /**
     * @param int $index
     * @throws DataItemNotFoundException
     * @return \Convo\Core\Media\IAudioFile
     */
    protected function _getSong( $index)
    {
        $model      =   $this->_getQueryModel();
        $real_index =   $model['playlist'][$index];
        
        $this->_logger->info( 'Getting song ['.$index.'] with real index ['.$real_index.']');
        
        foreach ( $this->getSongs() as $i=>$song)
        {
            $this->_logger->debug( 'Checking song ['.$song.']['.$real_index.']');
            
            if ( $i === $real_index) {
                return $song;
            }
        }
        
        throw new DataItemNotFoundException( 'Could not find post by real index ['.$real_index.']');
    }
    
    
    
    
    // PERSISTANT MODEL NAVI
    private function _getQueryModel()
    {
        $params =   $this->getService()->getComponentParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_INSTALLATION, $this);
        $model  =   $params->getServiceParam( self::PARAM_NAME_QUERY_MODEL);
        
        if ( empty( $model)) {
            $this->_logger->info( 'There is no saved model. Going to create default one.');
            $model   =   [
                'playing' => false,
                'post_index' => 0,
                'loop_status' => empty( $this->_defaultLoop) ? false : $this->getService()->evaluateString( $this->_defaultLoop),
                'shuffle_status' => empty( $this->_defaultShuffle) ? false : $this->getService()->evaluateString( $this->_defaultShuffle),
                'playlist' => [],
                'song_offset' => 0,
                'arguments' => [],
            ];
            $this->_saveQueryModel( $model);
        }
        
        return $model;
    }
    
    private function _saveQueryModel( $model)
    {
        $this->_logger->debug( 'Saving query model ['.print_r( $model, true).']['.$this.']');
        $params =   $this->getService()->getComponentParams( \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_INSTALLATION, $this);
        $params->setServiceParam( self::PARAM_NAME_QUERY_MODEL, $model);
    }
    
    
    // UTIL
    public function __toString()
    {
        return parent::__toString().'['.$this->_id.']';
    }

}
