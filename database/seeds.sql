/* Default admin user (password: admin123) */
INSERT IGNORE INTO users (name, email, password_hash, role) VALUES
('Administrador', 'admin@techchallenge.com', '$2y$10$s1Dm4kBrYhLeFo.rJqBKtu6UqhCUKbUnq2vrvxnCJosTFt0wxK8UW', 'admin');

INSERT INTO services (name, description, price_in_cents, estimated_duration_minutes, active) VALUES
('Troca de óleo', 'Troca de óleo do motor', 15000, 30, 1),
('Alinhamento', 'Alinhamento de direção', 8000, 45, 1),
('Balanceamento', 'Balanceamento de rodas', 6000, 30, 1),
('Revisão completa', 'Revisão completa do veículo', 35000, 180, 1),
('Troca de pastilhas de freio', 'Substituição das pastilhas de freio', 25000, 60, 1),
('Troca de correia dentada', 'Substituição da correia dentada e tensor', 45000, 120, 1),
('Limpeza de bicos injetores', 'Limpeza e teste dos bicos injetores', 18000, 90, 1),
('Troca de velas', 'Substituição das velas de ignição', 12000, 45, 1),
('Sangria de freios', 'Troca do fluido de freio', 8500, 40, 1),
('Geometria completa', 'Alinhamento e balanceamento completo', 15000, 60, 1),
('Troca de amortecedores', 'Substituição dos amortecedores', 55000, 150, 1),
('Diagnóstico eletrônico', 'Análise completa via scanner', 12000, 60, 1);

INSERT INTO parts (name, code, description, price_in_cents, stock_quantity, minimum_stock, active) VALUES
('Óleo Motor 5W30', 'OIL-5W30-001', 'Óleo sintético 5W30 - 1L', 4500, 50, 10, 1),
('Filtro de Óleo', 'FLT-OIL-001', 'Filtro de óleo universal', 2500, 100, 20, 1),
('Filtro de Ar', 'FLT-AIR-001', 'Filtro de ar universal', 3000, 80, 15, 1),
('Pastilha de Freio Dianteira', 'BRK-PAD-F01', 'Pastilha de freio dianteira', 12000, 40, 8, 1),
('Pastilha de Freio Traseira', 'BRK-PAD-R01', 'Pastilha de freio traseira', 10000, 40, 8, 1),
('Disco de Freio', 'BRK-DSC-001', 'Disco de freio ventilado', 18000, 20, 5, 1),
('Pneu 185/65 R15', 'TIRE-185-65-15', 'Pneu aro 15', 35000, 30, 8, 1),
('Bateria 60Ah', 'BAT-60AH-001', 'Bateria automotiva 60Ah', 45000, 15, 5, 1),
('Correia Dentada', 'BLT-TIMING-01', 'Kit correia dentada com tensor e rolamentos', 28000, 25, 6, 1),
('Vela de Ignição', 'SPK-PLG-001', 'Vela de ignição iridium', 3500, 60, 12, 1),
('Filtro de Combustível', 'FLT-FUEL-001', 'Filtro de combustível', 4000, 45, 10, 1),
('Fluido de Freio DOT4', 'FLD-BRK-DOT4', 'Fluido de freio DOT4 - 500ml', 2800, 50, 15, 1),
('Amortecedor Dianteiro', 'SHK-FRT-001', 'Amortecedor dianteiro pressurizado', 22000, 12, 4, 1),
('Amortecedor Traseiro', 'SHK-REAR-001', 'Amortecedor traseiro pressurizado', 20000, 12, 4, 1),
('Filtro de Cabine', 'FLT-CAB-001', 'Filtro de ar condicionado', 3500, 35, 10, 1),
('Lâmpada H4', 'LMP-H4-001', 'Lâmpada farol H4 12V 60/55W', 2500, 80, 20, 1),
('Junta do Coletor', 'GSK-MNF-001', 'Junta do coletor de admissão', 8500, 20, 5, 1),
('Sensor de Oxigênio', 'SNS-O2-001', 'Sonda lambda universal', 18500, 10, 3, 1);

INSERT INTO clients (id, name, cpf, email, phone, address) VALUES
(1, 'João da Silva', '11144477735', 'joao.silva@email.com', '(11) 98765-4321', 'Rua das Flores, 123 - São Paulo/SP'),
(2, 'Maria Santos', '52998224725', 'maria.santos@email.com', '(11) 91234-5678', 'Av. Paulista, 456 - São Paulo/SP'),
(3, 'Empresa ABC Ltda', NULL, 'contato@empresaabc.com', '(11) 3456-7890', 'Rua Comercial, 789 - São Paulo/SP'),
(4, 'Pedro Oliveira', '39053344705', 'pedro.oliveira@email.com', '(11) 99988-7766', 'Rua dos Pinheiros, 234 - São Paulo/SP'),
(5, 'Ana Costa', '84434895002', 'ana.costa@email.com', '(11) 97766-5544', 'Av. Brigadeiro Faria Lima, 567 - São Paulo/SP'),
(6, 'Carlos Mendes', '74621650030', 'carlos.mendes@email.com', '(11) 96655-4433', 'Rua Augusta, 890 - São Paulo/SP'),
(7, 'Auto Center XYZ', NULL, 'financeiro@autocenterxyz.com', '(11) 3333-4444', 'Av. Industrial, 1234 - São Paulo/SP'),
(8, 'Juliana Ferreira', '63172839002', 'juliana.ferreira@email.com', '(11) 95544-3322', 'Rua Consolação, 345 - São Paulo/SP');

UPDATE clients SET cnpj = '11222333000181' WHERE id = 3;
UPDATE clients SET cnpj = '44555666000172' WHERE id = 7;

INSERT INTO vehicles (plate, brand, model, year, color, client_id) VALUES
('ABC1D23', 'Honda', 'Civic', 2020, 'Preto', 1),
('XYZ9F87', 'Toyota', 'Corolla', 2019, 'Branco', 2),
('DEF5G45', 'Ford', 'Focus', 2021, 'Prata', 1),
('GHI2J34', 'Chevrolet', 'Onix', 2022, 'Vermelho', 3),
('JKL8M56', 'Volkswagen', 'Golf', 2020, 'Azul', 2),
('MNO3P12', 'Fiat', 'Uno', 2018, 'Branco', 1),
('PQR7S89', 'Renault', 'Sandero', 2021, 'Cinza', 3),
('STU4V67', 'Hyundai', 'HB20', 2022, 'Preto', 2);

INSERT INTO service_orders (order_number, status, client_id, vehicle_id, description, quotation_total_in_cents, quotation_approved_at, started_at, finished_at, created_at, updated_at) VALUES
('OS-2025-0001', 'delivered', 1, 1, 'Troca de óleo e filtros', 26500, '2025-10-25 08:25:00', '2025-10-25 08:30:00', '2025-10-25 09:15:00', '2025-10-25 08:00:00', '2025-10-25 10:00:00');

INSERT INTO service_order_services (service_order_id, service_id, quantity, unit_price_in_cents, subtotal_in_cents) VALUES
(1, 1, 1, 15000, 15000);

INSERT INTO service_order_parts (service_order_id, part_id, quantity, unit_price_in_cents, subtotal_in_cents) VALUES
(1, 1, 4, 4500, 18000),
(1, 2, 1, 2500, 2500);

INSERT INTO service_order_status_history (service_order_id, status, notes, changed_at) VALUES
(1, 'received', 'Ordem recebida', '2025-10-25 08:00:00'),
(1, 'in_diagnostic', 'Diagnóstico iniciado', '2025-10-25 08:10:00'),
(1, 'awaiting_approval', 'Orçamento enviado ao cliente', '2025-10-25 08:20:00'),
(1, 'in_execution', 'Serviço aprovado, iniciando execução', '2025-10-25 08:30:00'),
(1, 'finished', 'Serviço concluído', '2025-10-25 09:15:00'),
(1, 'delivered', 'Veículo entregue ao cliente', '2025-10-25 10:00:00');

INSERT INTO service_orders (order_number, status, client_id, vehicle_id, description, quotation_total_in_cents, quotation_approved_at, started_at, created_at, updated_at) VALUES
('OS-2025-0002', 'in_execution', 2, 2, 'Revisão completa dos 60.000 km', 72000, '2025-10-25 08:25:00', '2025-10-31 09:00:00', '2025-10-30 14:00:00', '2025-10-31 09:00:00');

INSERT INTO service_order_services (service_order_id, service_id, quantity, unit_price_in_cents, subtotal_in_cents) VALUES
(2, 4, 1, 35000, 35000),
(2, 2, 1, 8000, 8000),
(2, 3, 1, 6000, 6000);

INSERT INTO service_order_parts (service_order_id, part_id, quantity, unit_price_in_cents, subtotal_in_cents) VALUES
(2, 1, 5, 4500, 22500),
(2, 2, 1, 2500, 2500),
(2, 3, 1, 3000, 3000);

INSERT INTO service_order_status_history (service_order_id, status, notes, changed_at) VALUES
(2, 'received', NULL, '2025-10-30 14:00:00'),
(2, 'in_diagnostic', 'Verificando todos os sistemas', '2025-10-30 14:30:00'),
(2, 'awaiting_approval', 'Orçamento: R$ 720,00', '2025-10-30 16:00:00'),
(2, 'in_execution', 'Cliente aprovou por telefone', '2025-10-31 09:00:00');

INSERT INTO service_orders (order_number, status, client_id, vehicle_id, description, quotation_total_in_cents, quotation_approved_at, diagnostic_notes, created_at, updated_at) VALUES
('OS-2025-0003', 'awaiting_approval', 1, 3, 'Barulho nos freios ao frear', 91000, NULL, 'Pastilhas e discos dianteiros desgastados. Necessário substituição urgente para segurança.', '2025-10-31 10:00:00', '2025-10-31 11:30:00');

INSERT INTO service_order_services (service_order_id, service_id, quantity, unit_price_in_cents, subtotal_in_cents) VALUES
(3, 5, 1, 25000, 25000);

INSERT INTO service_order_parts (service_order_id, part_id, quantity, unit_price_in_cents, subtotal_in_cents) VALUES
(3, 4, 1, 12000, 12000),
(3, 6, 2, 18000, 36000);

INSERT INTO service_order_status_history (service_order_id, status, notes, changed_at) VALUES
(3, 'received', NULL, '2025-10-31 10:00:00'),
(3, 'in_diagnostic', NULL, '2025-10-31 10:15:00'),
(3, 'awaiting_approval', 'Orçamento enviado por email', '2025-10-31 11:30:00');

INSERT INTO service_orders (order_number, status, client_id, vehicle_id, description, created_at, updated_at) VALUES
('OS-2025-0004', 'received', 3, 4, 'Veículo não está ligando', '2025-10-31 13:00:00', '2025-10-31 13:00:00');

INSERT INTO service_order_status_history (service_order_id, status, notes, changed_at) VALUES
(4, 'received', 'Aguardando mecânico disponível', '2025-10-31 13:00:00');

INSERT INTO service_orders (order_number, status, client_id, vehicle_id, description, diagnostic_notes, created_at, updated_at) VALUES
('OS-2025-0005', 'in_diagnostic', 2, 5, 'Ar condicionado não esfria', 'Verificando sistema de ar condicionado. Possível falta de gás refrigerante.', '2025-10-31 14:00:00', '2025-10-31 14:30:00');

INSERT INTO service_order_status_history (service_order_id, status, notes, changed_at) VALUES
(5, 'received', NULL, '2025-10-31 14:00:00'),
(5, 'in_diagnostic', 'Diagnóstico em andamento', '2025-10-31 14:30:00');

INSERT INTO service_orders (order_number, status, client_id, vehicle_id, description, quotation_total_in_cents, quotation_approved_at, started_at, finished_at, created_at, updated_at) VALUES
('OS-2025-0006', 'finished', 3, 7, 'Troca dos 4 pneus', 140000, '2025-10-25 08:25:00', '2025-10-30 10:00:00', '2025-10-30 11:30:00', '2025-10-29 16:00:00', '2025-10-30 11:30:00');

INSERT INTO service_order_parts (service_order_id, part_id, quantity, unit_price_in_cents, subtotal_in_cents) VALUES
(6, 7, 4, 35000, 140000);

INSERT INTO service_order_status_history (service_order_id, status, notes, changed_at) VALUES
(6, 'received', NULL, '2025-10-29 16:00:00'),
(6, 'in_diagnostic', 'Pneus com desgaste irregular', '2025-10-29 16:15:00'),
(6, 'awaiting_approval', 'Orçamento: R$ 1.400,00', '2025-10-29 16:45:00'),
(6, 'in_execution', 'Aprovado, instalando pneus novos', '2025-10-30 10:00:00'),
(6, 'finished', 'Pneus instalados e calibrados', '2025-10-30 11:30:00');

INSERT INTO service_orders (order_number, status, client_id, vehicle_id, description, quotation_total_in_cents, quotation_approved_at, cancelled, cancelled_at, cancellation_reason, created_at, updated_at) VALUES
('OS-2025-0007', 'awaiting_approval', 1, 6, 'Troca de bateria', 50000, NULL, 1, '2025-10-28 15:00:00', 'Cliente desistiu do serviço', '2025-10-28 09:00:00', '2025-10-28 15:00:00');

INSERT INTO service_order_parts (service_order_id, part_id, quantity, unit_price_in_cents, subtotal_in_cents) VALUES
(7, 8, 1, 45000, 45000);

INSERT INTO service_order_status_history (service_order_id, status, notes, changed_at) VALUES
(7, 'received', NULL, '2025-10-28 09:00:00'),
(7, 'in_diagnostic', 'Bateria sem carga', '2025-10-28 09:30:00'),
(7, 'awaiting_approval', 'Cliente vai pensar', '2025-10-28 10:00:00');

INSERT INTO service_orders (order_number, status, client_id, vehicle_id, description, quotation_total_in_cents, quotation_approved_at, started_at, finished_at, delivered_at, created_at, updated_at) VALUES
('OS-2025-0007', 'delivered', 2, 8, 'Manutenção preventiva', 49000, '2025-10-25 08:25:00', '2025-10-24 08:00:00', '2025-10-24 10:30:00', '2025-10-24 17:00:00', '2025-10-23 15:00:00', '2025-10-24 17:00:00');

INSERT INTO service_order_services (service_order_id, service_id, quantity, unit_price_in_cents, subtotal_in_cents) VALUES
(7, 1, 1, 15000, 15000),
(7, 2, 1, 8000, 8000),
(7, 3, 1, 6000, 6000);

INSERT INTO service_order_parts (service_order_id, part_id, quantity, unit_price_in_cents, subtotal_in_cents) VALUES
(7, 1, 3, 7000, 21000),
(7, 2, 1, 2500, 2500);

INSERT INTO service_order_status_history (service_order_id, status, notes, changed_at) VALUES
(7, 'received', NULL, '2025-10-23 15:00:00'),
(7, 'in_diagnostic', NULL, '2025-10-23 15:30:00'),
(7, 'awaiting_approval', 'Orçamento aprovado rapidamente', '2025-10-23 16:00:00'),
(7, 'in_execution', NULL, '2025-10-24 08:00:00'),
(7, 'finished', 'Tudo OK', '2025-10-24 10:30:00'),
(7, 'delivered', 'Cliente satisfeito', '2025-10-24 17:00:00');

INSERT INTO notifications (client_id, service_order_id, type, event, subject, message, status, created_at, sent_at) VALUES
(1, 1, 'email', 'order_received', 'Ordem de Serviço Recebida', 'Sua OS #OS-2025-0001 foi recebida e será diagnosticada em breve.', 'sent', '2025-10-25 14:00:00', '2025-10-25 14:00:05'),
(1, 1, 'email', 'quotation_ready', 'Orçamento Pronto', 'Seu orçamento está pronto. Total: R$ 215,00', 'sent', '2025-10-25 15:00:00', '2025-10-25 15:00:03'),
(1, 1, 'email', 'quotation_approved', 'Orçamento Aprovado', 'Obrigado por aprovar! Iniciando os reparos.', 'sent', '2025-10-25 15:30:00', '2025-10-25 15:30:02'),
(2, 2, 'email', 'order_received', 'Ordem de Serviço Recebida', 'Sua OS #OS-2025-0002 foi recebida.', 'sent', '2025-10-26 09:00:00', '2025-10-26 09:00:04'),
(2, 2, 'email', 'quotation_ready', 'Orçamento Pronto', 'Seu orçamento está pronto. Total: R$ 630,00', 'sent', '2025-10-26 10:30:00', '2025-10-26 10:30:01'),
(2, 2, 'sms', 'quotation_reminder', 'Lembrete: Orçamento Aguardando', 'Lembrete: Seu orçamento #OS-2025-0002 aguarda aprovação há 24h.', 'sent', '2025-10-27 10:30:00', '2025-10-27 10:30:12'),
(4, 4, 'email', 'order_received', 'Ordem de Serviço Recebida', 'Sua OS #OS-2025-0004 foi recebida.', 'sent', '2025-10-28 11:00:00', '2025-10-28 11:00:03'),
(4, 4, 'email', 'quotation_ready', 'Orçamento Pronto', 'Diagnóstico concluído. Aguardando orçamento.', 'pending', '2025-10-28 14:00:00', NULL);

INSERT INTO part_reservations (service_order_id, part_id, quantity, status, reserved_at, expires_at, confirmed_at, cancelled_at) VALUES
(1, 1, 3, 'confirmed', '2025-10-25 14:30:00', '2025-10-26 14:30:00', '2025-10-25 15:30:00', NULL),
(1, 2, 1, 'confirmed', '2025-10-25 14:30:00', '2025-10-26 14:30:00', '2025-10-25 15:30:00', NULL),
(2, 3, 2, 'reserved', '2025-10-26 10:30:00', '2025-10-27 10:30:00', NULL, NULL),
(2, 4, 4, 'reserved', '2025-10-26 10:30:00', '2025-10-27 10:30:00', NULL, NULL),
(2, 5, 1, 'reserved', '2025-10-26 10:30:00', '2025-10-27 10:30:00', NULL, NULL),
(3, 6, 1, 'confirmed', '2025-10-27 09:00:00', '2025-10-28 09:00:00', '2025-10-27 10:00:00', NULL),
(4, 7, 1, 'reserved', '2025-10-28 14:00:00', '2025-10-29 14:00:00', NULL, NULL),
(5, 8, 2, 'expired', '2025-10-29 10:00:00', '2025-10-30 10:00:00', NULL, NULL);
