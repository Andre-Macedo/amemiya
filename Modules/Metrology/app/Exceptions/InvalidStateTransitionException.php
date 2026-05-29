<?php

namespace Modules\Metrology\Exceptions;

use Exception;
use Modules\Metrology\Enums\ItemStatus;

class InvalidStateTransitionException extends Exception
{
    public function __construct(ItemStatus $from, ItemStatus $to)
    {
        parent::__construct("Transição de estado inválida: Não é permitido mudar de '{$from->getLabel()}' para '{$to->getLabel()}'.");
    }
}
