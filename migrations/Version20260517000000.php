<?php

declare(strict_types=1);

namespace App\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema for OS Service.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            "CREATE TABLE IF NOT EXISTS users (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    name VARCHAR(255) NOT NULL,\n" .
                "    email VARCHAR(255) NOT NULL UNIQUE,\n" .
                "    password_hash VARCHAR(255) NOT NULL,\n" .
                "    role VARCHAR(50) NOT NULL DEFAULT 'user',\n" .
                "    active TINYINT(1) NOT NULL DEFAULT 1,\n" .
                "    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
                "    INDEX idx_email (email),\n" .
                "    INDEX idx_active (active)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS clients (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    name VARCHAR(255) NOT NULL,\n" .
                "    cpf VARCHAR(14) NULL UNIQUE,\n" .
                "    cnpj VARCHAR(14) NULL UNIQUE,\n" .
                "    email VARCHAR(255) NULL,\n" .
                "    phone VARCHAR(20) NULL,\n" .
                "    address TEXT NULL,\n" .
                "    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
                "    INDEX idx_cpf (cpf),\n" .
                "    INDEX idx_cnpj (cnpj),\n" .
                "    INDEX idx_email (email),\n" .
                "    INDEX idx_name (name)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS vehicles (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    plate VARCHAR(7) NOT NULL UNIQUE,\n" .
                "    brand VARCHAR(100) NOT NULL,\n" .
                "    model VARCHAR(100) NOT NULL,\n" .
                "    year INT NOT NULL,\n" .
                "    color VARCHAR(50) NULL,\n" .
                "    client_id INT NOT NULL,\n" .
                "    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
                "    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,\n" .
                "    INDEX idx_plate (plate),\n" .
                "    INDEX idx_client (client_id)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS services (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    name VARCHAR(255) NOT NULL,\n" .
                "    description TEXT NULL,\n" .
                "    price_in_cents INT NOT NULL,\n" .
                "    estimated_duration_minutes INT NULL,\n" .
                "    active TINYINT(1) NOT NULL DEFAULT 1,\n" .
                "    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
                "    INDEX idx_name (name),\n" .
                "    INDEX idx_active (active)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS parts (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    name VARCHAR(255) NOT NULL,\n" .
                "    code VARCHAR(100) NULL UNIQUE,\n" .
                "    description TEXT NULL,\n" .
                "    price_in_cents INT NOT NULL,\n" .
                "    stock_quantity INT NOT NULL DEFAULT 0,\n" .
                "    minimum_stock INT NULL,\n" .
                "    active TINYINT(1) NOT NULL DEFAULT 1,\n" .
                "    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
                "    INDEX idx_code (code),\n" .
                "    INDEX idx_name (name),\n" .
                "    INDEX idx_active (active),\n" .
                "    INDEX idx_low_stock (stock_quantity, minimum_stock)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS service_orders (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    order_number VARCHAR(50) NOT NULL UNIQUE,\n" .
                "    client_id INT NOT NULL,\n" .
                "    vehicle_id INT NOT NULL,\n" .
                "    status VARCHAR(50) NOT NULL DEFAULT 'received',\n" .
                "    description TEXT NULL,\n" .
                "    diagnostic_notes TEXT NULL,\n" .
                "    quotation_total_in_cents INT NULL,\n" .
                "    quotation_approved_at TIMESTAMP NULL,\n" .
                "    quotation_sent_at TIMESTAMP NULL,\n" .
                "    quotation_rejected_at TIMESTAMP NULL,\n" .
                "    rejection_reason VARCHAR(255) NULL,\n" .
                "    cancelled BOOLEAN DEFAULT FALSE NOT NULL,\n" .
                "    cancelled_at TIMESTAMP NULL,\n" .
                "    cancellation_reason VARCHAR(255) NULL,\n" .
                "    quotation_reminders_sent INT DEFAULT 0 NOT NULL,\n" .
                "    last_reminder_sent_at TIMESTAMP NULL,\n" .
                "    started_at TIMESTAMP NULL,\n" .
                "    finished_at TIMESTAMP NULL,\n" .
                "    delivered_at TIMESTAMP NULL,\n" .
                "    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n" .
                "    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,\n" .
                "    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE RESTRICT,\n" .
                "    INDEX idx_order_number (order_number),\n" .
                "    INDEX idx_client (client_id),\n" .
                "    INDEX idx_vehicle (vehicle_id),\n" .
                "    INDEX idx_status (status),\n" .
                "    INDEX idx_created_at (created_at),\n" .
                "    INDEX idx_quotation_sent_at (quotation_sent_at),\n" .
                "    INDEX idx_cancelled (cancelled)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS service_order_services (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    service_order_id INT NOT NULL,\n" .
                "    service_id INT NOT NULL,\n" .
                "    quantity INT NOT NULL DEFAULT 1,\n" .
                "    unit_price_in_cents INT NOT NULL,\n" .
                "    subtotal_in_cents INT NOT NULL,\n" .
                "    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,\n" .
                "    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE RESTRICT,\n" .
                "    INDEX idx_service_order (service_order_id),\n" .
                "    INDEX idx_service (service_id)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS service_order_parts (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    service_order_id INT NOT NULL,\n" .
                "    part_id INT NOT NULL,\n" .
                "    quantity INT NOT NULL,\n" .
                "    unit_price_in_cents INT NOT NULL,\n" .
                "    subtotal_in_cents INT NOT NULL,\n" .
                "    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,\n" .
                "    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE RESTRICT,\n" .
                "    INDEX idx_service_order (service_order_id),\n" .
                "    INDEX idx_part (part_id)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS service_order_status_history (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    service_order_id INT NOT NULL,\n" .
                "    status VARCHAR(50) NOT NULL,\n" .
                "    notes TEXT NULL,\n" .
                "    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,\n" .
                "    INDEX idx_service_order (service_order_id),\n" .
                "    INDEX idx_changed_at (changed_at)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS notifications (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    client_id INT NOT NULL,\n" .
                "    service_order_id INT NULL,\n" .
                "    type VARCHAR(50) NOT NULL COMMENT 'email, sms, push, whatsapp',\n" .
                "    event VARCHAR(100) NOT NULL COMMENT 'order_received, diagnostic_started, quotation_ready, etc',\n" .
                "    subject VARCHAR(255) NOT NULL,\n" .
                "    message TEXT NOT NULL,\n" .
                "    status VARCHAR(20) DEFAULT 'pending' NOT NULL COMMENT 'pending, sent, failed',\n" .
                "    error_message TEXT NULL,\n" .
                "    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    sent_at TIMESTAMP NULL,\n" .
                "    CONSTRAINT fk_notification_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,\n" .
                "    CONSTRAINT fk_notification_service_order FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE SET NULL,\n" .
                "    INDEX idx_notification_client (client_id),\n" .
                "    INDEX idx_notification_service_order (service_order_id),\n" .
                "    INDEX idx_notification_status (status),\n" .
                "    INDEX idx_notification_created (created_at)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addSql(
            "CREATE TABLE IF NOT EXISTS part_reservations (\n" .
                "    id INT AUTO_INCREMENT PRIMARY KEY,\n" .
                "    service_order_id INT NOT NULL,\n" .
                "    part_id INT NOT NULL,\n" .
                "    quantity INT NOT NULL,\n" .
                "    status VARCHAR(20) DEFAULT 'reserved' NOT NULL COMMENT 'reserved, confirmed, cancelled, expired',\n" .
                "    reserved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n" .
                "    expires_at TIMESTAMP NOT NULL,\n" .
                "    confirmed_at TIMESTAMP NULL,\n" .
                "    cancelled_at TIMESTAMP NULL,\n" .
                "    CONSTRAINT fk_reservation_service_order FOREIGN KEY (service_order_id) REFERENCES service_orders(id) ON DELETE CASCADE,\n" .
                "    CONSTRAINT fk_reservation_part FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE RESTRICT,\n" .
                "    INDEX idx_reservation_service_order (service_order_id),\n" .
                "    INDEX idx_reservation_part (part_id),\n" .
                "    INDEX idx_reservation_status (status),\n" .
                "    INDEX idx_reservation_expires (expires_at)\n" .
                ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS part_reservations');
        $this->addSql('DROP TABLE IF EXISTS notifications');
        $this->addSql('DROP TABLE IF EXISTS service_order_status_history');
        $this->addSql('DROP TABLE IF EXISTS service_order_parts');
        $this->addSql('DROP TABLE IF EXISTS service_order_services');
        $this->addSql('DROP TABLE IF EXISTS service_orders');
        $this->addSql('DROP TABLE IF EXISTS parts');
        $this->addSql('DROP TABLE IF EXISTS services');
        $this->addSql('DROP TABLE IF EXISTS vehicles');
        $this->addSql('DROP TABLE IF EXISTS clients');
        $this->addSql('DROP TABLE IF EXISTS users');
    }
}
