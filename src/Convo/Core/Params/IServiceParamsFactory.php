<?php declare(strict_types=1);

namespace Convo\Core\Params;


interface IServiceParamsFactory
{
	/**
	 * @param \Convo\Core\Params\IServiceParamsScope $scope
	 * @return \Convo\Core\Params\IServiceParams
	 */
	public function getServiceParams( \Convo\Core\Params\IServiceParamsScope $scope);

}