<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqCommandPublisher
{
    public function __construct(private AMQPStreamConnection $connection) {}

    public function publish(string $exchange, string $routingKey, array $payload): void
    {
        $channel = $this->connection->channel();
        $channel->exchange_declare($exchange, 'direct', false, true, false);

        $message = new AMQPMessage(json_encode($payload), [
            'content_type' => 'application/json',
        ]);

        $channel->basic_publish($message, $exchange, $routingKey);
        $channel->close();
    }
}
