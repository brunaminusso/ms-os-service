<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Domain\Entity\Client;
use App\Domain\Entity\Notification;
use PHPUnit\Framework\TestCase;

final class NotificationTest extends TestCase
{
    public function testMarkAsSentAndFailed(): void
    {
        $client = new Client('John Doe');
        $notification = new Notification($client, 'email', 'created', 'Subject', 'Body');

        $this->assertSame('pending', $notification->getStatus());
        $this->assertNull($notification->getSentAt());

        $notification->markAsSent();
        $this->assertSame('sent', $notification->getStatus());
        $this->assertNotNull($notification->getSentAt());

        $notification->markAsFailed('error');
        $this->assertSame('failed', $notification->getStatus());
        $this->assertSame('error', $notification->getErrorMessage());
    }
}
