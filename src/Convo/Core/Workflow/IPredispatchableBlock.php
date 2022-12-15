<?php declare(strict_types=1);

namespace Convo\Core\Workflow;


interface IPredispatchableBlock extends IRunnableBlock
{
	

    
    public function preDispatch( IConvoRequest $request, IConvoResponse $response);
}
