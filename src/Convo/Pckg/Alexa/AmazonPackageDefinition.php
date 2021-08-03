<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa;

use Convo\Core\Factory\AbstractPackageDefinition;
use Convo\Core\Factory\ComponentDefinition;
use Convo\Core\Factory\IComponentFactory;

class AmazonPackageDefinition extends AbstractPackageDefinition
{
	const NAMESPACE	=	'convo-alexa';

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    /**
     * @var \Convo\Core\IServiceDataProvider
     */
    private $_convoServiceDataProvider;

	public function __construct(
	    \Psr\Log\LoggerInterface $logger,
        \Convo\Core\Util\IHttpFactory $httpFactory,
        \Convo\Core\IServiceDataProvider $convoServiceDataProvider
    ) {
        $this->_httpFactory = $httpFactory;
        $this->_convoServiceDataProvider = $convoServiceDataProvider;

		parent::__construct( $logger, self::NAMESPACE, __DIR__);
	}

	protected function _initDefintions()
    {
        return [
            new ComponentDefinition(
                $this->getNamespace(),
                '\Convo\Pckg\Alexa\Elements\GetAmazonUserElement',
                'Init Amazon user',
                'Initialize an Amazon user.',
                [
                    'initialized_user_var' => [
                        'editor_type' => 'text',
                        'editor_properties' => [],
                        'defaultValue' => 'user',
                        'name' => 'Name',
                        'description' => 'Name under which to store the loaded user object in the context',
                        'valueType' => 'string'
                    ],
                    '_preview_angular' => [
                        'type' => 'html',
                        'template' => '<div class="code">' .
                            'Load Amazon User and set it as <span class="statement"><b>{{ component.properties.initialized_user_var }}</b></span>' .
                            '</div>'
                    ],
                    '_workflow' => 'read',
                    '_help' =>  array(
                        'type' => 'file',
                        'filename' => 'get-amazon-user-element.html'
                    ),
                    '_factory' => new class ($this->_httpFactory, $this->_convoServiceDataProvider) implements IComponentFactory
                    {
                        private $_httpFactory;
                        private $_convoServiceDataProvider;

                        public function __construct($httpFactory, $convoServiceDataProvider)
                        {
                            $this->_httpFactory = $httpFactory;
                            $this->_convoServiceDataProvider = $convoServiceDataProvider;
                        }

                        public function createComponent($properties, $service)
                        {
                            return new \Convo\Pckg\Alexa\Elements\GetAmazonUserElement($properties, $this->_httpFactory, $this->_convoServiceDataProvider);
                        }
                    }
                ]
            )
        ];
    }

}
