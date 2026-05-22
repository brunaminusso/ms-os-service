<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Domain\Entity\Client;
use App\Domain\Entity\Vehicle;
use App\Domain\Entity\ServiceOrder;
use App\Domain\ValueObject\VehiclePlate;
use App\Domain\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;

final class ServiceOrderTest extends TestCase
{
    public function testStatusTransitionsAndQuotationApproval(): void
    {
        $client = new Client('Foo');
        $plate = new VehiclePlate('ABC1234');
        $vehicle = new Vehicle($plate, 'Brand', 'Model', 2020, $client);

        $order = new ServiceOrder('ORD-1', $client, $vehicle);

        $this->assertSame(OrderStatus::RECEIVED, $order->getStatus());

        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $this->assertSame(OrderStatus::IN_DIAGNOSTIC, $order->getStatus());

        $order->changeStatus(OrderStatus::AWAITING_APPROVAL);
        $this->assertSame(OrderStatus::AWAITING_APPROVAL, $order->getStatus());

        $this->assertFalse($order->isQuotationApproved());
        $order->approveQuotation();
        $this->assertTrue($order->isQuotationApproved());
    }

    public function testSendQuotationSetsSentAt(): void
    {
        $client = new Client('Foo');
        $plate = new VehiclePlate('ABC1234');
        $vehicle = new Vehicle($plate, 'Brand', 'Model', 2020, $client);

        $order = new ServiceOrder('ORD-2', $client, $vehicle);
        $this->assertNull($order->getQuotationSentAt());

        $order->sendQuotation();
        $this->assertNotNull($order->getQuotationSentAt());
        $this->assertSame(OrderStatus::AWAITING_APPROVAL, $order->getStatus());
    }
}
