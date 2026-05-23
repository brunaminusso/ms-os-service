<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Domain\Entity\Client;
use App\Domain\ValueObject\CPF;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testSettersAndCpf(): void
    {
        $client = new Client('Jane Doe');
        $this->assertSame('Jane Doe', $client->getName());

        $client->setName('Jane Smith');
        $this->assertSame('Jane Smith', $client->getName());

        $client->setEmail('a@b.com');
        $this->assertSame('a@b.com', $client->getEmail());

        $cpf = new CPF('12345678909');
        $client->setCpf($cpf);
        $this->assertSame($cpf->getValue(), $client->getCpf()->getValue());
    }
}
