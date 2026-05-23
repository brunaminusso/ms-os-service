<?php
declare(strict_types=1);

namespace Tests\Unit\Smoke;

use PHPUnit\Framework\TestCase;

final class LoadClassesTest extends TestCase
{
    public function testHighRoiClassesAutoload(): void
    {
        $classes = [
            \App\Infrastructure\Observability\PrometheusMetricsRegistry::class,
            \App\Infrastructure\Exception\ValidationException::class,
            \App\Infrastructure\Messaging\RabbitMqCommandPublisher::class,
            \App\Infrastructure\Persistence\Repository\ServiceOrderRepository::class,
            \App\Domain\Service\PartReservationService::class,
            \App\Domain\Service\NotificationService::class,
            \App\Domain\Repository\ClientRepositoryInterface::class,
        ];

        foreach ($classes as $class) {
            $exists = class_exists($class) || interface_exists($class) || trait_exists($class);
            $this->assertTrue($exists, "Class/interface/trait $class should be autoloadable");
        }
    }
}
