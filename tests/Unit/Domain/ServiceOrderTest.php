<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entity\Client;
use App\Domain\Entity\Part;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Entity\Vehicle;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use App\Domain\Enum\OrderStatus;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ServiceOrderTest extends TestCase
{
    public function testChangeStatusSetsTimestampsAndAverageExecutionTime(): void
    {
        $client = new Client('Cliente');
        $vehicle = new Vehicle(new VehiclePlate('ABC1234'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-1000', $client, $vehicle);

        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        // sendQuotation will move to AWAITING_APPROVAL
        $order->sendQuotation();
        $order->changeStatus(OrderStatus::IN_EXECUTION);
        $this->assertNotNull($order->getStartedAt());

        // simulate some execution time by directly setting finishedAt via reflection
        $order->changeStatus(OrderStatus::FINISHED);
        $this->assertNotNull($order->getFinishedAt());

        $avg = $order->getAverageExecutionTime();
        $this->assertIsInt($avg);
        $this->assertGreaterThanOrEqual(0, $avg);
    }

    public function testApproveAndRejectQuotationFlow(): void
    {
        $client = new Client('Cliente');
        $vehicle = new Vehicle(new VehiclePlate('DEF5678'), 'Brand', 'Model', 2021, $client);
        $order = new ServiceOrder('ORD-1001', $client, $vehicle);

        // move to diagnostic and send quotation
        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->sendQuotation();

        $this->assertNotNull($order->getQuotationSentAt());

        $order->approveQuotation();
        $this->assertTrue($order->isQuotationApproved());

        // create a new order to test rejection
        $order2 = new ServiceOrder('ORD-1002', $client, $vehicle);
        $order2->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order2->sendQuotation();
        $order2->rejectQuotation('Preço alto');
        $this->assertTrue($order2->isQuotationRejected());
        $this->assertEquals('Preço alto', $order2->getRejectionReason());
    }

    public function testShouldSendReminderAndAutoCancelBasedOnSentAt(): void
    {
        $client = new Client('Cliente');
        $vehicle = new Vehicle(new VehiclePlate('GHI9012'), 'Brand', 'Model', 2022, $client);
        $order = new ServiceOrder('ORD-1003', $client, $vehicle);

        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->sendQuotation();

        // set quotationSentAt to 25 hours ago
        $past = (new DateTimeImmutable())->modify('-25 hours');
        $rp = new \ReflectionProperty($order, 'quotationSentAt');
        $rp->setAccessible(true);
        $rp->setValue($order, $past);

        $rp2 = new \ReflectionProperty($order, 'quotationRemindersSent');
        $rp2->setAccessible(true);
        $rp2->setValue($order, 0);

        $this->assertTrue($order->shouldSendReminder());

        // set quotationSentAt to 49 hours ago for auto-cancel
        $past48 = (new DateTimeImmutable())->modify('-49 hours');
        $rp->setValue($order, $past48);
        $this->assertTrue($order->shouldAutoCancelForTimeout());
    }

    public function testAddPartRecalculatesQuotationTotal(): void
    {
        $client = new Client('Cliente');
        $vehicle = new Vehicle(new VehiclePlate('JKL3456'), 'Brand', 'Model', 2019, $client);
        $order = new ServiceOrder('ORD-1004', $client, $vehicle);

        $part = new Part('Filtro', new Money(15.50));
        $part->addStock(10);

        $order->addPart($part, 2);

        $total = $order->getQuotationTotal();
        $this->assertNotNull($total);
        $this->assertEquals(31.00, $total->getAmount());
    }
}
