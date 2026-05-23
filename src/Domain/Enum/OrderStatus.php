<?php

declare(strict_types=1);

namespace App\Domain\Enum;

/**
 * OrderStatus - Estados da Ordem de Serviço
 */
enum OrderStatus: string
{
    case RECEIVED = 'received';
    case IN_DIAGNOSTIC = 'in_diagnostic';
    case AWAITING_APPROVAL = 'awaiting_approval';
    case IN_EXECUTION = 'in_execution';
    case FINISHED = 'finished';
    case DELIVERED = 'delivered';

    public function getLabel(): string
    {
        return match ($this) {
            self::RECEIVED => 'Recebida',
            self::IN_DIAGNOSTIC => 'Em diagnóstico',
            self::AWAITING_APPROVAL => 'Aguardando aprovação',
            self::IN_EXECUTION => 'Em execução',
            self::FINISHED => 'Finalizada',
            self::DELIVERED => 'Entregue',
        };
    }

    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return match ($this) {
            self::RECEIVED => in_array($newStatus, [self::IN_DIAGNOSTIC]),
            self::IN_DIAGNOSTIC => in_array($newStatus, [self::AWAITING_APPROVAL]),
            self::AWAITING_APPROVAL => in_array($newStatus, [self::IN_EXECUTION, self::RECEIVED]),
            self::IN_EXECUTION => in_array($newStatus, [self::FINISHED]),
            self::FINISHED => in_array($newStatus, [self::DELIVERED]),
            self::DELIVERED => false,
        };
    }

    /**
     * @return array<string, string[]>
     */
    public static function getAllowedTransitions(): array
    {
        return [
            self::RECEIVED->value => [self::IN_DIAGNOSTIC->value],
            self::IN_DIAGNOSTIC->value => [self::AWAITING_APPROVAL->value],
            self::AWAITING_APPROVAL->value => [self::IN_EXECUTION->value, self::RECEIVED->value],
            self::IN_EXECUTION->value => [self::FINISHED->value],
            self::FINISHED->value => [self::DELIVERED->value],
            self::DELIVERED->value => [],
        ];
    }
}
