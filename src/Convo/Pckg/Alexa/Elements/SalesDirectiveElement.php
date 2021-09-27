<?php

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Adapters\Alexa\AmazonCommandResponse;
use Convo\Core\Adapters\Alexa\IAlexaResponseType;
use Convo\Core\Factory\InvalidComponentDataException;
use Convo\Core\Util\IHttpFactory;
use Convo\Core\Workflow\IConvoRequest;
use Convo\Core\Workflow\IConvoResponse;
use Psr\Http\Client\ClientExceptionInterface;

class SalesDirectiveElement extends \Convo\Core\Workflow\AbstractWorkflowContainerComponent implements \Convo\Core\Workflow\IConversationElement
{

	const GET_ISP_PRODUCTS = '/v1/users/~current/skills/~current/inSkillProducts';

	/**
	 * @var \Convo\Core\Workflow\IConversationElement[]
	 */
	private $_onProductNoFound = array();

	private $_token;
	private $_salesDirective;
	private $_productFilterValue;
	private $_productUpsellVar;
	private $_productUpsellMessage;

	/**
	 * @var \Convo\Core\Util\IHttpFactory
	 */
	private $_httpFactory;

	public function __construct($properties, $httpFactory)
	{
		parent::__construct($properties);

		$this->_token = $properties['name'] ?? '';
		$this->_salesDirective = $properties['sales_directive'] ?? 'Buy';
		$this->_productFilterValue = $properties['product_filter_value'] ?? '';
		$this->_productUpsellVar = $properties['product_upsell_var'] ?? 'product_upsell_var';
		$this->_productUpsellMessage = $properties['product_upsell_message'] ?? '';

		foreach ($properties['on_product_not_found'] as $element) {
			$this->_onProductNoFound[] = $element;
			$this->addChild($element);
		}

		$this->_httpFactory = $httpFactory;
	}

	public function read(IConvoRequest $request, IConvoResponse $response)
	{
		$productFilterValue = $this->evaluateString($this->_productFilterValue);

		if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
			/**
			 * @var AmazonCommandResponse $response
			 */
			try
			{
				$products = $this->_getInSkillProducts($request);
				$this->_logger->debug("Printing products: " . json_encode($products, JSON_PRETTY_PRINT));
				if (isset($products['inSkillProducts'])) {
					$targetProduct = array_filter($products['inSkillProducts'], function ($product) use ($productFilterValue) {
						return (strtolower(trim($product['name'])) === strtolower(trim($productFilterValue)));
					});

					$targetProduct = array_values($targetProduct);

					if (!empty($targetProduct)) {
						$directive = $this->_getDirective($targetProduct[0]);
						$response->prepareResponse(IAlexaResponseType::SALES_DIRECTIVE);
						$response->setSalesDirective($directive);
						throw new \Convo\Core\SessionEndedException();
					} else {
						$this->_logger->info( 'The product you requested was not found.');
						foreach ( $this->_onProductNoFound as $element) {
							$element->read( $request, $response);
						}
					}
				}
			}
			catch (ClientExceptionInterface $e)
			{
				$this->_logger->error($e->getMessage());
			}
		}
	}

	private function _getInSkillProducts(AmazonCommandRequest $request) {
		$platformData = $request->getPlatformData();
		$client = $this->_httpFactory->getHttpClient();

		$productsUri = $this->_httpFactory->buildUri($platformData['context']['System']['apiEndpoint'] . self::GET_ISP_PRODUCTS);
		$this->_logger->debug('Products URI [' . $productsUri . ']');
		$ispProductsApiRequest = $this->_httpFactory->buildRequest(IHttpFactory::METHOD_GET, $productsUri->__toString(), [
			'Accept-Language' => $request->getLocale(),
			'Authorization' => 'Bearer ' . $platformData['context']['System']['apiAccessToken']
		]);

		$res = $client->sendRequest($ispProductsApiRequest);

		return json_decode($res->getBody()->__toString(), true);
	}

	private function _getDirective($product) {
		$token = $this->evaluateString($this->_token);
		$salesDirective = $this->evaluateString($this->_salesDirective);

		$directive = [
			'type' => 'Connections.SendRequest',
			'token' => $token
		];

		switch ($salesDirective) {
			case 'buy':
				$directive['name'] = 'Buy';
				$directive['payload']['InSkillProduct']['productId'] = $product['productId'];
				break;
			case 'upsell';
				$productUpsellVar = $this->evaluateString($this->_productUpsellVar);

				$scope_type	= \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST;
				$params = $this->getService()->getServiceParams($scope_type);
				$params->setServiceParam($productUpsellVar, $product);

				$productUpsellMessage = $this->evaluateString($this->_productUpsellMessage);
				$directive['name'] = 'Upsell';
				$directive['payload']['InSkillProduct']['productId'] = $product['productId'];
				$directive['payload']['upsellMessage'] = $productUpsellMessage;
				break;
			case 'cancel';
				$directive['name'] = 'Cancel';
				$directive['payload']['InSkillProduct']['productId'] = $product['productId'];
				break;
			default:
				throw new InvalidComponentDataException('Sales directive of type [' . $salesDirective . '] is not supported.');
		}

		$this->_logger->debug("Preparing [" . $salesDirective . "] directive [" .  json_encode($directive, JSON_PRETTY_PRINT) . "]");

		return $directive;
	}
}