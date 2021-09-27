<?php declare(strict_types=1);

namespace Convo\Pckg\Alexa\Elements;

use Convo\Core\Adapters\Alexa\AmazonCommandRequest;
use Convo\Core\Util\IHttpFactory;
use Convo\Core\Workflow\AbstractWorkflowComponent;
use Convo\Core\Workflow\IConversationElement;
use Psr\Http\Client\ClientExceptionInterface;

class GetInSkillProductsElement extends AbstractWorkflowComponent implements IConversationElement
{
	const GET_ISP_PRODUCTS = '/v1/users/~current/skills/~current/inSkillProducts';

    private $_name;
    private $_shouldGetProductById;
    private $_productId;
    private $_filterByEntitlement;
    private $_filterByProductType;

    /**
     * @var \Convo\Core\Util\IHttpFactory
     */
    private $_httpFactory;

    public function __construct($properties, $httpFactory)
    {
        parent::__construct($properties);

        $this->_name = $properties['name'] ?? 'status';
        $this->_shouldGetProductById = $properties['should_get_product_by_id'] ?? false;
        $this->_productId = $properties['product_id'] ?? '';
        $this->_filterByEntitlement = $properties['filter_by_entitlement'] ?? 'ALL';
        $this->_filterByProductType = $properties['filter_by_product_type'] ?? 'ALL';

        $this->_httpFactory = $httpFactory;
    }

    public function read(\Convo\Core\Workflow\IConvoRequest $request, \Convo\Core\Workflow\IConvoResponse $response)
    {
        $scope_type	= \Convo\Core\Params\IServiceParamsScope::SCOPE_TYPE_REQUEST;
		$params = $this->getService()->getServiceParams($scope_type);
		$name = $this->evaluateString($this->_name);

        if (is_a($request, '\Convo\Core\Adapters\Alexa\AmazonCommandRequest')) {
			try
			{
				$products = $this->_getInSkillProducts($request);
				$this->_logger->debug("Printing products: " . json_encode($products, JSON_PRETTY_PRINT));
				$params->setServiceParam($name, $products);
			}
			catch (ClientExceptionInterface $e)
			{
				$this->_logger->error($e->getMessage());
				$params->setServiceParam($name, null);
			}
        }
    }

    private function _getInSkillProducts(AmazonCommandRequest $request) {
		$shouldGetProductById = $this->evaluateString($this->_shouldGetProductById);

		$platformData = $request->getPlatformData();
        $client = $this->_httpFactory->getHttpClient();

        $productsUri = $this->_httpFactory->buildUri(
			$platformData['context']['System']['apiEndpoint'] . self::GET_ISP_PRODUCTS,
			$this->_getQueryParams()
		);

		if ($shouldGetProductById) {
			$productId = $this->evaluateString($this->_productId);
			$productsUri = $this->_httpFactory->buildUri(
				$platformData['context']['System']['apiEndpoint'] . self::GET_ISP_PRODUCTS . '/' . $productId
			);
		}

		$this->_logger->debug('Products URI [' . $productsUri . ']');
        $ispProductsApiRequest = $this->_httpFactory->buildRequest(IHttpFactory::METHOD_GET, $productsUri->__toString(), [
			'Accept-Language' => $request->getLocale(),
			'Authorization' => 'Bearer ' . $platformData['context']['System']['apiAccessToken']
		]);

        $res = $client->sendRequest($ispProductsApiRequest);

        return json_decode($res->getBody()->__toString(), true);
    }

	private function _getQueryParams() {
		$filterByEntitlement = $this->evaluateString($this->_filterByEntitlement);
		$filterByProductType = $this->evaluateString($this->_filterByProductType);

		$params = [];


		if (strtoupper($filterByEntitlement) === 'ENTITLED') {
			$params['entitled'] = 'ENTITLED';
		}

		if (strtoupper($filterByEntitlement) === 'NOT_ENTITLED') {
			$params['purchasable'] = 'PURCHASABLE';
			$params['entitled'] = 'NOT_ENTITLED';
		}

		if (strtoupper($filterByProductType) === 'CONSUMABLE') {
			$params['productType'] = 'CONSUMABLE';
		}

		if (strtoupper($filterByProductType) === 'SUBSCRIPTION') {
			$params['productType'] = 'SUBSCRIPTION';
		}

		if (strtoupper($filterByProductType) === 'ENTITLEMENT') {
			$params['productType'] = 'ENTITLEMENT';
		}

		return $params;
	}
}
