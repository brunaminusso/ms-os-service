<?php

declare(strict_types=1);

namespace Tests\Domain\Enum;

use App\Domain\Enum\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function testAllowedTransitions(): void
    {
        $this->assertTrue(OrderStatus::RECEIVED->canTransitionTo(OrderStatus::IN_DIAGNOSTIC));
        $this->assertFalse(OrderStatus::RECEIVED->canTransitionTo(OrderStatus::DELIVERED));
        $this->assertTrue(OrderStatus::AWAITING_APPROVAL->canTransitionTo(OrderStatus::RECEIVED));
        $this->assertFalse(OrderStatus::DELIVERED->canTransitionTo(OrderStatus::RECEIVED));
    }

    public function testLabels(): void
    {
        $this->assertSame('Recebida', OrderStatus::RECEIVED->getLabel());
        $this->assertSame('Finalizada', OrderStatus::FINISHED->getLabel());
        $this->assertSame('Em execução', OrderStatus::IN_EXECUTION->getLabel());
    }

    public function testAllowedTransitionsMap(): void
    {
        $map = OrderStatus::getAllowedTransitions();

        $this->assertSame(['in_diagnostic'], $map[OrderStatus::RECEIVED->value]);
        $this->assertSame(['in_execution', 'received'], $map[OrderStatus::AWAITING_APPROVAL->value]);
        $this->assertSame([], $map[OrderStatus::DELIVERED->value]);
    }
}
