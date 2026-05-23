# OS Service

[![Coverage](https://img.shields.io/github/actions/workflow/status/brunaminusso/ms-os-service/tests.yml?branch=main&label=coverage)](https://github.com/brunaminusso/ms-os-service/actions/workflows/tests.yml)

Servico responsavel por gerenciar ordens de servico (OS) e os cadastros base
(clientes, veiculos, servicos e pecas), mantendo o limite de 3 microsservicos.

## Responsabilidades

- Abertura de OS
- Atualizacao de status oficial
- Consulta de OS e historico
- CRUD de clientes, veiculos, servicos e pecas

## Banco de dados

- MySQL

## Migrations e seed

```bash
composer migrate
composer seed
```

## OpenAPI

- Documento: `public/openapi.json`

## Evidencias de cobertura

- Workflow: https://github.com/brunaminusso/ms-os-service/actions/workflows/tests.yml
- Artefato de cobertura publicado no CI: `build/coverage.xml`
- Verificacao local opcional: `composer coverage:check`

## CI / Sonar

- O workflow de CI executa build, testes, análise estática e um scan SonarQube opcional.
- Para habilitar o Sonar no CI, defina os secrets do repositório: `SONAR_HOST_URL`, `MS_SONAR_TOKEN` e, se necessário, `SONAR_ORGANIZATION`.
- O workflow só executa o step do Sonar quando `MS_SONAR_TOKEN` estiver presente.
- O gate principal de cobertura fica centralizado no Sonar; `composer coverage:check` pode ser usado localmente como validacao adicional.

## Teste BDD

- Fluxo principal da saga: [tests/Application/Saga/ServiceOrderSagaFlowBddTest.php](tests/Application/Saga/ServiceOrderSagaFlowBddTest.php)

## Consumo de eventos (RabbitMQ)

```bash
php scripts/consume-events.php
```

## Saga Pattern (orquestrada)

O OS Service atua como orquestrador central do fluxo transacional:

1) OS criada -> envia comando para Billing gerar orcamento.
2) Billing confirma orcamento -> OS muda para aguardando aprovacao.
3) Pagamento aprovado -> OS envia comando para Execution iniciar.
4) Execution atualiza status -> OS reflete o andamento.

Compensacoes:
- Falha ao gerar orcamento: OS cancela a ordem e libera reservas.
- Falha ao iniciar execucao: OS cancela a ordem e solicita reembolso ao Billing.
- Pagamento recusado/expirado: OS cancela a ordem e libera reservas.
- Reembolso falhou: OS registra a falha e notifica.

## Endpoints (planejados)

- POST `/service-orders`
- PATCH `/service-orders/{id}/status`
- GET `/service-orders/{id}`

## Como rodar (dev)

```bash
cp .env.example .env
composer start
```

Ou com Docker:

```bash
docker-compose up -d
```
