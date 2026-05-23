<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Part;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Repository\ServiceOrderRepositoryInterface;
use App\Domain\Enum\OrderStatus;

/**
 * Serviço responsável por notificar sobre atualizações de estoque
 * Conforme diagrama Event Storming (Imagem 4 - Estoque):
 *
 * POL: "Enviar notificação sobre atualização do estoque ao mecânico"
 */
class StockNotificationService
{
    public function __construct(
        private ServiceOrderRepositoryInterface $serviceOrderRepository,
        private NotificationService $notificationService
    ) {
    }

    /**
     * Notifica clientes/mecânicos de ordens aguardando uma peça específica
     * quando ela volta ao estoque
     *
     * @param Part $part Peça que foi reabastecida
     * @return array<int, array<string,mixed>> Lista de ordens notificadas
     */
    public function notifyPartRestocked(Part $part): array
    {
        // Busca todas as ordens que podem estar aguardando esta peça
        $pendingOrders = $this->findOrdersAwaitingPart($part);

        if (empty($pendingOrders)) {
            return [];
        }

        $notifiedOrders = [];

        foreach ($pendingOrders as $order) {
            // Notifica o cliente da ordem que a peça está disponível
            $this->notificationService->notifyPartBackInStock(
                $order->getClient(),
                $part->getName(),
                $part->getStockQuantity(),
                $order
            );

            $notifiedOrders[] = [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'client_name' => $order->getClient()->getName(),
                'part_name' => $part->getName(),
            ];
        }

        return $notifiedOrders;
    }

    /**
     * Encontra ordens de serviço que podem estar aguardando uma peça específica
     *
     * Critérios:
     * - Ordem em status RECEIVED ou IN_DIAGNOSTIC (ainda montando orçamento)
     * - Ordem contém a peça solicitada
     * - Ordem não está cancelada
     */
    /**
     * @return array<int, ServiceOrder>
     */
    private function findOrdersAwaitingPart(Part $part): array
    {
        $awaitingOrders = [];

        // Busca ordens nos status onde ainda podem adicionar peças
        $statuses = [OrderStatus::RECEIVED, OrderStatus::IN_DIAGNOSTIC];

        foreach ($statuses as $status) {
            $orders = $this->serviceOrderRepository->findByStatus($status);

            foreach ($orders as $order) {
                if ($order->isCancelled()) {
                    continue;
                }

                // Verifica se a ordem contém esta peça
                foreach ($order->getParts() as $orderPart) {
                    if ($orderPart->getPart()->getId() === $part->getId()) {
                        $awaitingOrders[] = $order;
                        break; // Não precisa verificar outras peças desta ordem
                    }
                }
            }
        }

        return $awaitingOrders;
    }

    /**
     * Verifica se uma peça atingiu estoque crítico e envia alertas
     *
     * @param Part $part Peça a verificar
     * @return bool True se alerta foi enviado
     */
    public function checkLowStockAlert(Part $part): bool
    {
        if (!$part->isLowStock()) {
            return false;
        }

        // Aqui poderia enviar alerta para o estoquista/administrador
        // Por enquanto apenas retorna true indicando que está em estoque baixo
        return true;
    }
}
