<?php declare(strict_types=1);

use Convo\Core\Admin\AdminUser;
use Convo\Core\ConvoServiceInstance;
use Convo\Core\Expression\EvaluationContext;
use Convo\Core\Expression\ExpressionFunctionProviderInterface;
use Convo\Core\Factory\ConvoServiceFactory;
use Convo\Core\Params\IServiceParamsFactory;
use Convo\Core\Util\EchoLogger;
use Convo\Pckg\Core\Elements\LoopElement;
use Convo\Pckg\Core\Elements\TextResponseElement;
use PHPUnit\Framework\TestCase;

class ConvoServiceInstanceTest extends TestCase
{
    private $_logger;

    private $_evaluationContext;

    public function setUp(): void
    {
        $this->_logger = new EchoLogger();
        $this->_evaluationContext = $this->_evalContext = new EvaluationContext(
            $this->_logger,
            new class implements ExpressionFunctionProviderInterface {
                public function getFunctions()
                {
                    return [];
                }
            }
        );
    }

    public function testParentChildRelations()
    {
        $service = new ConvoServiceInstance(
            $this->_logger,
            $this->_evaluationContext,
            new class () implements IServiceParamsFactory
            {
                public function getServiceParams( \Convo\Core\Params\IServiceParamsScope $scope)
                {
                    return null;
                }
            },
            new AdminUser('', '', '', ''),
            'test'
        );

        $text_response = new TextResponseElement([
            '_component_id' => ConvoServiceFactory::generateId()
        ]);
        $service->addChild($text_response); // should get overwritten

        $for_loop = new LoopElement([
            '_component_id' => ConvoServiceFactory::generateId(),
            'data_collection' => '${[]}',
            'item' => 'item',
            'loop_until' => null, 'offset' => null, 'limit' => null,
            'elements' => [$text_response]
        ]);
        $service->addChild($for_loop);

        $this->assertEquals(1, count($service->getChildren()), 'Service must have exactly 1 child.');
        $this->assertEquals(LoopElement::class, get_class($service->getChildren()[0]), 'Service\'s child must be ['.LoopElement::class.']');

        $this->assertEquals(1, count($for_loop->getChildren()), 'For Loop must have exactly 1 child.');
        $this->assertEquals(TextResponseElement::class, get_class($for_loop->getChildren()[0]), 'For Loop\'s child must be ['.TextResponseElement::class.']');
    }
}