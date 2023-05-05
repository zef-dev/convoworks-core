<?php declare(strict_types=1);

namespace Convo\Core\Workflow;

/**
 * 
 * @author Tole
 *
 */
interface ITimezoneAwareRequest extends IConvoRequest
{
	/**
	 * @return \DateTimeZone
	 */
    public function getTimeZone();
    
    /**
     * @param \DateTimeZone $timezone
     */
    public function setTimeZone( $timezone);

}
