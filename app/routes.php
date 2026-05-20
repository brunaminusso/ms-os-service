<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Presentation\Controller\ServiceOrderController;
use App\Presentation\Controller\ClientController;
use App\Presentation\Controller\VehicleController;
use App\Presentation\Controller\ServiceController;
use App\Presentation\Controller\PartController;
use App\Presentation\Controller\HealthController;
use App\Presentation\Controller\MetricsController;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        return $response;
    });

    $app->get('/metrics', [MetricsController::class, 'index']);

    $app->group('/api/v1', function (Group $group) {
        $group->get('/health', [HealthController::class, 'check']);
        $group->get('/ready', [HealthController::class, 'ready']);

        $group->get('/clients', [ClientController::class, 'list']);
        $group->get('/clients/{id}', [ClientController::class, 'get']);
        $group->post('/clients', [ClientController::class, 'create']);
        $group->put('/clients/{id}', [ClientController::class, 'update']);
        $group->delete('/clients/{id}', [ClientController::class, 'delete']);

        $group->get('/vehicles', [VehicleController::class, 'list']);
        $group->get('/vehicles/client/{clientId}', [VehicleController::class, 'listByClient']);
        $group->get('/vehicles/{id}', [VehicleController::class, 'get']);
        $group->post('/vehicles', [VehicleController::class, 'create']);
        $group->put('/vehicles/{id}', [VehicleController::class, 'update']);
        $group->delete('/vehicles/{id}', [VehicleController::class, 'delete']);

        $group->get('/services', [ServiceController::class, 'list']);
        $group->get('/services/{id}', [ServiceController::class, 'get']);
        $group->post('/services', [ServiceController::class, 'create']);
        $group->put('/services/{id}', [ServiceController::class, 'update']);
        $group->delete('/services/{id}', [ServiceController::class, 'delete']);

        $group->get('/parts', [PartController::class, 'list']);
        $group->get('/parts/low-stock', [PartController::class, 'getLowStock']);
        $group->get('/parts/code/{code}', [PartController::class, 'getByCode']);
        $group->get('/parts/{id}', [PartController::class, 'get']);
        $group->post('/parts', [PartController::class, 'create']);
        $group->put('/parts/{id}', [PartController::class, 'update']);
        $group->delete('/parts/{id}', [PartController::class, 'delete']);
        $group->patch('/parts/{id}/stock', [PartController::class, 'updateStock']);

        $group->get('/service-orders', [ServiceOrderController::class, 'list']);
        $group->get('/service-orders/{id}', [ServiceOrderController::class, 'get']);
        $group->post('/service-orders', [ServiceOrderController::class, 'create']);
        $group->patch('/service-orders/{id}/status', [ServiceOrderController::class, 'updateStatus']);
        $group->post('/service-orders/{id}/approve', [ServiceOrderController::class, 'approveQuotation']);
        $group->post('/service-orders/{id}/reject', [ServiceOrderController::class, 'rejectQuotation']);
        $group->post('/service-orders/{id}/services', [ServiceOrderController::class, 'addService']);
        $group->post('/service-orders/{id}/parts', [ServiceOrderController::class, 'addPart']);
        $group->delete('/service-orders/{id}/services/{serviceId}', [ServiceOrderController::class, 'removeService']);
        $group->delete('/service-orders/{id}/parts/{partId}', [ServiceOrderController::class, 'removePart']);
        $group->get('/analytics/average-execution-time', [ServiceOrderController::class, 'getAverageExecutionTime']);
    });
};
