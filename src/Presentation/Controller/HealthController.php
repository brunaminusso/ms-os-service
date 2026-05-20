<?php

declare(strict_types=1);

namespace App\Presentation\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HealthController extends BaseController
{
    public function check(Request $request, Response $response): Response
    {
        $data = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'service' => 'Tech Challenge API',
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }

    public function ready(Request $request, Response $response): Response
    {
        $data = [
            'status' => 'ready',
            'timestamp' => date('Y-m-d H:i:s'),
            'service' => 'Tech Challenge API',
        ];

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
    }
}
