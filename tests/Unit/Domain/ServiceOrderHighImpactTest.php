<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Entity\Client;
use App\Domain\Entity\Part;
use App\Domain\Entity\Service;
use App\Domain\Entity\ServiceOrder;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\VehiclePlate;
use App\Domain\Entity\Vehicle;
use App\Domain\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ServiceOrderHighImpactTest extends TestCase
{
    public function testAddServiceAndPartRecalculateQuotation(): void
    {
        $client = new Client('Cliente X');
        $vehicle = new Vehicle(new VehiclePlate('AAA1111'), 'Brand', 'Model', 2020, $client);

        $order = new ServiceOrder('ORD-900', $client, $vehicle);

        $service = new Service('Serv A', new Money(100.0));
        $part = new Part('Peça A', new Money(50.0));
        $part->addStock(10);

        $order->addService($service, 2); // 2 * 100 = 200
        $order->addPart($part, 3); // 3 * 50 = 150

        $total = $order->getQuotationTotal();
        $this->assertNotNull($total);
        $this->assertSame(35000, $total->getAmountInCents());
    }

    public function testChangeStatusAndAverageExecutionTime(): void
    {
        $client = new Client('Cliente Y');
        $vehicle = new Vehicle(new VehiclePlate('BBB2222'), 'Brand', 'Model', 2021, $client);
        $order = new ServiceOrder('ORD-901', $client, $vehicle);

        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->changeStatus(OrderStatus::AWAITING_APPROVAL);
        $order->changeStatus(OrderStatus::IN_EXECUTION);
        $this->assertNotNull($order->getStartedAt());

        // simulate finish by changing status to FINISHED
        $order->changeStatus(OrderStatus::FINISHED);
        $this->assertNotNull($order->getFinishedAt());
        $this->assertIsInt($order->getAverageExecutionTime());
    }

    public function testSendApproveRejectQuotationAndReminders(): void
    {
        $client = new Client('Cliente Z');
        $vehicle = new Vehicle(new VehiclePlate('CCC3333'), 'Brand', 'Model', 2022, $client);
        $order = new ServiceOrder('ORD-902', $client, $vehicle);

        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->sendQuotation();
        $this->assertSame(OrderStatus::AWAITING_APPROVAL, $order->getStatus());
        $this->assertNotNull($order->getQuotationSentAt());

        // set sentAt to 25 hours ago via reflection to trigger shouldSendReminder
        $rp = new ReflectionProperty($order, 'quotationSentAt');
        $rp->setAccessible(true);
        $past = (new \DateTimeImmutable())->modify('-25 hours');
        $rp->setValue($order, $past);

        $this->assertTrue($order->shouldSendReminder());

        $order->sendReminder();
        $this->assertFalse($order->shouldSendReminder()); // already sent

        // test auto cancel after 48 hours
        $rp->setValue($order, (new \DateTimeImmutable())->modify('-49 hours'));
        $this->assertTrue($order->shouldAutoCancelForTimeout());

        // approve/reject flows require status AWAITING_APPROVAL
        $order->approveQuotation();
        $this->assertTrue($order->isQuotationApproved());
    }

    public function testCancelRestrictionsAndPartStockException(): void
    {
        $client = new Client('Cliente W');
        $vehicle = new Vehicle(new VehiclePlate('DDD4444'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-903', $client, $vehicle);

        // cannot cancel delivered
        $order->changeStatus(OrderStatus::IN_DIAGNOSTIC);
        $order->changeStatus(OrderStatus::AWAITING_APPROVAL);
        $order->changeStatus(OrderStatus::IN_EXECUTION);
        $order->changeStatus(OrderStatus::FINISHED);
        $order->changeStatus(OrderStatus::DELIVERED);

        $this->expectException(\DomainException::class);
        $order->cancel('motivo');
    }

    public function testAddPartThrowsWhenInsufficientStock(): void
    {
        $client = new Client('Cliente V');
        $vehicle = new Vehicle(new VehiclePlate('EEE5555'), 'Brand', 'Model', 2020, $client);
        $order = new ServiceOrder('ORD-904', $client, $vehicle);

        $part = new Part('Peça B', new Money(20.0));
        // no stock

        $this->expectException(\DomainException::class);
        $order->addPart($part, 1);
    }
}
