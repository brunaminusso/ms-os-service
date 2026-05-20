<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Client;
use App\Domain\Entity\Notification;
use App\Domain\Entity\ServiceOrder;
use App\Domain\Repository\NotificationRepositoryInterface;

class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository
    ) {
    }

    public function notifyServiceOrderCreated(ServiceOrder $order): void
    {
        $notification = new Notification(
            $order->getClient(),
            'email',
            'order_received',
            'Ordem de Serviço Recebida',
            sprintf(
                'Sua ordem de serviço %s foi recebida com sucesso. Veículo: %s - %s.',
                $order->getOrderNumber(),
                $order->getVehicle()->getPlate()->getFormatted(),
                $order->getVehicle()->getModel()
            ),
            $order
        );

        $this->notificationRepository->save($notification);
        $notification->markAsSent();
        $this->notificationRepository->save($notification);
    }

    public function notifyStatusChanged(ServiceOrder $order, string $previousStatus): void
    {
        $messages = [
            'in_diagnostic' => 'Diagnóstico iniciado - Estamos avaliando seu veículo.',
            'awaiting_approval' => 'Orçamento disponível - Acesse para aprovar ou rejeitar.',
            'in_execution' => 'Serviço em execução - Nossos mecânicos estão trabalhando no seu veículo.',
            'finished' => 'Serviço finalizado - Seu veículo está pronto!',
            'delivered' => 'Veículo entregue - Obrigado pela confiança!',
        ];

        $message = $messages[$order->getStatus()->value] ?? 'Status atualizado: ' . $order->getStatus()->getLabel();

        $statusLabel = $previousStatus ? "Status anterior: $previousStatus. " : '';

        $notification = new Notification(
            $order->getClient(),
            'email',
            'status_changed_' . $order->getStatus()->value,
            'Atualização da Ordem de Serviço ' . $order->getOrderNumber(),
            sprintf(
                '%s%s Ordem de Serviço: %s',
                $statusLabel,
                $message,
                $order->getOrderNumber()
            ),
            $order
        );

        $this->notificationRepository->save($notification);
        $notification->markAsSent();
        $this->notificationRepository->save($notification);
    }

    public function notifyQuotationCreated(ServiceOrder $order): void
    {
        $notification = new Notification(
            $order->getClient(),
            'email',
            'quotation_created',
            'Orçamento Disponível - ' . $order->getOrderNumber(),
            sprintf(
                'Seu orçamento está pronto! Valor total: R$ %s. Por favor, aprove ou rejeite em até 48 horas.',
                $order->getQuotationTotal()?->getFormatted()
            ),
            $order
        );

        $this->notificationRepository->save($notification);
        $notification->markAsSent();
        $this->notificationRepository->save($notification);
    }

    public function notifyQuotationReminder(ServiceOrder $order): void
    {
        $notification = new Notification(
            $order->getClient(),
            'email',
            'quotation_reminder',
            'LEMBRETE: Orçamento Aguardando Resposta - ' . $order->getOrderNumber(),
            sprintf(
                'Seu orçamento de R$ %s ainda está aguardando aprovação. Você tem até 24 horas para responder antes do cancelamento automático.',
                $order->getQuotationTotal()?->getFormatted()
            ),
            $order
        );

        $this->notificationRepository->save($notification);
        $notification->markAsSent();
        $this->notificationRepository->save($notification);
    }

    public function notifyQuotationApproved(ServiceOrder $order): void
    {
        $notification = new Notification(
            $order->getClient(),
            'email',
            'quotation_approved',
            'Orçamento Aprovado - ' . $order->getOrderNumber(),
            sprintf(
                'Seu orçamento foi aprovado! O serviço será iniciado em breve. Valor: R$ %s',
                $order->getQuotationTotal()?->getFormatted()
            ),
            $order
        );

        $this->notificationRepository->save($notification);
        $notification->markAsSent();
        $this->notificationRepository->save($notification);
    }

    public function notifyQuotationRejected(ServiceOrder $order): void
    {
        $notification = new Notification(
            $order->getClient(),
            'email',
            'quotation_rejected',
            'Orçamento Rejeitado - ' . $order->getOrderNumber(),
            'Orçamento rejeitado conforme solicitado. A ordem de serviço será cancelada.',
            $order
        );

        $this->notificationRepository->save($notification);
        $notification->markAsSent();
        $this->notificationRepository->save($notification);
    }

    public function notifyOrderCancelled(ServiceOrder $order, string $reason): void
    {
        $notification = new Notification(
            $order->getClient(),
            'email',
            'order_cancelled',
            'Ordem de Serviço Cancelada - ' . $order->getOrderNumber(),
            sprintf(
                'Sua ordem de serviço foi cancelada. Motivo: %s',
                $reason
            ),
            $order
        );

        $this->notificationRepository->save($notification);
        $notification->markAsSent();
        $this->notificationRepository->save($notification);
    }

    public function notifyPartUnavailable(ServiceOrder $order, string $partName): void
    {
        $notification = new Notification(
            $order->getClient(),
            'email',
            'part_unavailable',
            'Peça Indisponível - ' . $order->getOrderNumber(),
            sprintf(
                'A peça "%s" está temporariamente indisponível. Estamos providenciando a reposição.',
                $partName
            ),
            $order
        );

        $this->notificationRepository->save($notification);
        $notification->markAsSent();
        $this->notificationRepository->save($notification);
    }

    public function notifyPartBackInStock(Client $client, string $partName, int $availableQuantity, ?ServiceOrder $order = null): void
    {
        $notification = new Notification(
            $client,
            'email',
            'part_back_in_stock',
            'Peça Disponível em Estoque - ' . $partName,
            sprintf(
                'A peça "%s" agora está disponível em estoque! Quantidade disponível: %d unidade(s). %s',
                $partName,
                $availableQuantity,
                $order ? "Ordem de serviço: {$order->getOrderNumber()}" : ''
            ),
            $order
        );

        $this->notificationRepository->save($notification);
        $notification->markAsSent();
        $this->notificationRepository->save($notification);
    }
}
