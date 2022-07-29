<?php declare(strict_types=1);

namespace Convo\Pckg\Core\Elements;

use Convo\Core\Workflow\IOptionalElement;

abstract class AbstractElementQueue extends ElementCollection
{
    
    public function getElements() {
        $elements   =   parent::getElements();
        $filtered   =   [];
        
        foreach ( $elements as $element) {
            if ( $element instanceof IOptionalElement) {
                /* @var IOptionalElement $element*/
                if ( $element->isEnabled()) {
                    $filtered[] = $element;
                }
                continue;
            }
            $filtered[] = $element;
        }
        
        return $filtered;
    }
}
