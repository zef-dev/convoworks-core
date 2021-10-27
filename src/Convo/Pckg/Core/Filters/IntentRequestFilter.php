<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Filters;

use Convo\Core\Workflow\AbstractWorkflowContainerComponent;

class IntentRequestFilter extends AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IRequestFilter
{
    /**
	 * @var \Convo\Core\Intent\IIntentAdapter[]
	 */
    private $_adapters = [];

    private $_id;

    public function __construct($config)
    {
        parent::__construct( $config);

        foreach ( $config['readers'] as $reader) {
            $this->addAdapter( $reader);
        }

        $this->_id = $config['_component_id'] ?? ''; // todo generate default id
    }

    public function getId()
    {
        return $this->_id;
    }

    public function addAdapter( \Convo\Core\Intent\IIntentAdapter $adapter)
    {
        $this->_adapters[]  =   $adapter;
        $this->addChild( $adapter);
    }

    public function accepts( \Convo\Core\Workflow\IConvoRequest $request)
    {
        if ( !is_a( $request, '\Convo\Core\Workflow\IIntentAwareRequest')) {
            $this->_logger->notice('Request is not IIntentAware. Exiting.');
            return false;
        }

        $this->_logger->info( 'Request is intent request ['.$request.']');
        return true;
    }

    public function filter( \Convo\Core\Workflow\IConvoRequest $request)
    {
        /** @var \Convo\Core\Workflow\IIntentAwareRequest $request */

        $this->_logger->debug( 'Matching against intent ['.$request->getIntentName().']['.$request->getIntentPlatformId().']');

        foreach ($this->_adapters as $adapter)
        {
            $this->_logger->debug( 'Checking adapter ['.$adapter.']');

            if ($adapter->accepts($request)) {
                $this->_logger->info('Adapter ['.$adapter.'] accepts intent.');
                return $adapter->read($request);
            }
        }

        $this->_logger->debug( 'No match. Returning empty result.');

        return new \Convo\Core\Workflow\DefaultFilterResult();
    }

    // UTIL
    public function __toString()
    {
        return get_class($this).'['.$this->_id.']';
    }
}
