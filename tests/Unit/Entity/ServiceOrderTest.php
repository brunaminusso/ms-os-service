<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Domain\Entity\Client;
use App\Domain\Entity\Part;
use App\Domain\Entity\Service;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use App\Domain\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;

final class ServiceOrderTest extends TestCase
{
    public function testAddServiceAndRecalculateQuotation(): void
    {
        $client = new Client('Cliente A');
        $vehicle = new Vehicle(new VehiclePlate('AAA1111'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-100', $client, $vehicle);

        $service = new Service('Troca de óleo', new Money(50.0));

        $order->addService($service, 2);

        $this->assertNotNull($order->getQuotationTotal());
        $this->assertEquals(100.0, $order->getQuotationTotal()->getAmount());
    }

    public function testAddServiceThrowsWhenInvalidStatus(): void
    {
        $client = new Client('Cliente B');
        $vehicle = new Vehicle(new VehiclePlate('BBB2222'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-101', $client, $vehicle);

        // move through diagnostic to awaiting approval to block adding services
        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->changeStatus(OrderStatus::AWAITING_APPROVAL);

        $service = new Service('Alinhamento', new Money(30.0));

        $this->expectException(\DomainException::class);
        $order->addService($service, 1);
    }

    public function testSendAndApproveAndRejectQuotationFlows(): void
    {
        $client = new Client('Cliente C');
        $vehicle = new Vehicle(new VehiclePlate('CCC3333'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-102', $client, $vehicle);

        $this->assertEquals(OrderStatus::RECEIVED, $order->getStatus());

        // must be in diagnostic before sending quotation
        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->sendQuotation();
        $this->assertEquals(OrderStatus::AWAITING_APPROVAL, $order->getStatus());
        $this->assertNotNull($order->getQuotationSentAt());

        $order->approveQuotation();
        $this->assertTrue($order->isQuotationApproved());

        // create new order to test rejection (must move to diagnostic first)
        $order2 = new ServiceOrder('ORD-103', $client, $vehicle);
        $order2->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order2->sendQuotation();
        $order2->rejectQuotation('Cliente rejeitou');
        $this->assertTrue($order2->isQuotationRejected());
        $this->assertEquals('Cliente rejeitou', $order2->getRejectionReason());
    }

    public function testShouldSendReminderAndAutoCancel(): void
    {
        $client = new Client('Cliente D');
        $vehicle = new Vehicle(new VehiclePlate('DDD4444'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-104', $client, $vehicle);

        // move to diagnostic then send quotation
        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->sendQuotation();

        // set quotationSentAt in the past via reflection
        $ref = new \ReflectionProperty(ServiceOrder::class, 'quotationSentAt');
        $ref->setAccessible(true);
        $past = new \DateTimeImmutable('-50 hours');
        $ref->setValue($order, $past);

        $this->assertTrue($order->shouldAutoCancelForTimeout());

        // set to 25 hours for reminder
        $ref->setValue($order, new \DateTimeImmutable('-25 hours'));
        $this->assertTrue($order->shouldSendReminder());
    }

    public function testCancelRules(): void
    {
        $client = new Client('Cliente E');
        $vehicle = new Vehicle(new VehiclePlate('EEE5555'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-105', $client, $vehicle);

        // progress through allowed transitions to FINISHED
        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->changeStatus(OrderStatus::AWAITING_APPROVAL);
        $order->changeStatus(OrderStatus::IN_EXECUTION);
        $order->changeStatus(OrderStatus::FINISHED);

        $this->expectException(\DomainException::class);
        $order->cancel('Motivo');
    }

    public function testAddAndRemovePart(): void
    {
        $client = new Client('Cliente F');
        $vehicle = new Vehicle(new VehiclePlate('FFF6666'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-106', $client, $vehicle);

        $part = new Part('Filtro', new Money(20.0));
        $part->addStock(5);

        $order->addPart($part, 2);
        $this->assertEquals(40.0, $order->getQuotationTotal()->getAmount());

        $parts = $order->getParts()->toArray();
        $order->removePart($parts[0]);
        $this->assertEquals(0.0, $order->getQuotationTotal()->getAmount());
    }
}
