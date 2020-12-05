<?php declare(strict_types=1);

namespace Convo\Core\Factory;

interface ITemplateSource
{
    /**
     * @param string $templateId
     * @return array
	 * @throws \Convo\Core\ComponentNotFoundException
     */
    public function getTemplate( $templateId);
	
}