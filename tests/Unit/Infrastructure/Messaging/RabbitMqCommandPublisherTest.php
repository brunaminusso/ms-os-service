<?php

declare(strict_types=1);

namespace Tests\Unit\Infrastructure\Messaging;

use App\Infrastructure\Messaging\RabbitMqCommandPublisher;
use PHPUnit\Framework\TestCase;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class RabbitMqCommandPublisherTest extends TestCase
{
    public function testPublishUsesChannelMethods(): void
    {
        $channel = $this->getMockBuilder(stdClass::class)
            ->setMethods(['exchange_declare', 'basic_publish', 'close'])
            ->getMock();

        $channel->expects($this->once())->method('exchange_declare');
        $channel->expects($this->once())->method('basic_publish');
        $channel->expects($this->once())->method('close');

        $connection = $this->getMockBuilder(AMQPStreamConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $connection->method('channel')->willReturn($channel);

        $publisher = new RabbitMqCommandPublisher($connection);
        $publisher->publish('exchange', 'rk', ['a' => 1]);

        $this->addToAssertionCount(3);
    }
}
