<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Observability;

use App\Infrastructure\Observability\PrometheusMetricsRegistry;
use PHPUnit\Framework\TestCase;

class PrometheusMetricsRegistryTest extends TestCase
{
    public function testCountersAndHistogramRendered(): void
    {
        $r = new PrometheusMetricsRegistry('os-service-test');

        $r->incOrdersCreated('received');
        $r->incQuotationsCreated();
        $r->incPaymentsCreated();

        $r->observeHttpRequest('GET', '/health', 200, 0.045);

        $out = $r->render();

        $this->assertStringContainsString('app_orders_created_total', $out);
        $this->assertStringContainsString('app_quotations_created_total', $out);
        $this->assertStringContainsString('app_payments_created_total', $out);
        $this->assertStringContainsString('app_http_requests_total', $out);
        $this->assertStringContainsString('app_http_request_duration_seconds', $out);
    }
}
