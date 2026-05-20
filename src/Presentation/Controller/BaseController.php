<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

abstract class BaseController
{
    protected const CONTENT_TYPE_JSON = 'application/json';
    protected const MSG_NOT_FOUND_CLIENT = 'Cliente não encontrado';
    protected const MSG_NOT_FOUND_PART = 'Peça não encontrada';
    protected const MSG_NOT_FOUND_SERVICE = 'Serviço não encontrado';
    protected const MSG_NOT_FOUND_SERVICE_ORDER = 'Ordem de serviço não encontrada';
    protected const MSG_NOT_FOUND_VEHICLE = 'Veículo não encontrado';
}
