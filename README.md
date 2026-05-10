# Product Hub

HUB para receber e processar jobs de atualização de produtos via Amazon SQS.

## Stack

- **PHP** 8.4 + **Laravel** 13
- **Amazon SQS** — fila de mensagens
- **MySQL** — banco de dados
- **Redis** — cache e sessões
- **Docker** — containerização
- **Pest** — testes automatizados
- **Scramble** — documentação Swagger

---

## Instalação

```bash
# 1. Clone o repositório
git clone https://github.com/pedrobombig/product-hub.git
cd product-hub

# 2. Configure o ambiente
cp .env.example .env
```

O `.env.example` já vem configurado para uso local. Apenas as credenciais AWS precisam ser preenchidas:

```env
AWS_ACCESS_KEY_ID=sua_chave
AWS_SECRET_ACCESS_KEY=seu_secret
SQS_PREFIX=prefix
```

A aplicação estará disponível em `http://localhost:8080`. O worker SQS sobe automaticamente.

```bash
# 3. Suba os containers
docker compose up -d --build

# 4. Instale as dependências
docker compose exec app composer install

# 5. Gere a chave da aplicação
docker compose exec app php artisan key:generate

# 6. Rode as migrations
docker compose exec app php artisan migrate
```

---

## Documentação da API
Com a aplicação rodando, acesse:

```
http://localhost:8080/docs/api
```

Gerada automaticamente pelo **Scramble** a partir das anotações nos controllers.

---

## Testes

```bash
docker compose exec app ./vendor/bin/pest
```

Os testes usam SQLite em memória.

---

## Arquitetura

```
app/
├── Data/                   # Camada de dados: Models e Repositories concretos
├── Domain/                 # Regras de negócio: Actions, DTOs, Enums, Interfaces
├── Infrastructure/Sqs/     # Dispatcher: recebe mensagem SQS e despacha o job correto
└── Modules/                # HTTP: Controllers e Requests por módulo
```

### Logs e monitoramento

Todos os jobs registram na tabela `job_logs`:

| Campo            | Descrição                                                  |
|------------------|------------------------------------------------------------|
| `job_type`       | Tipo da operação (update_stock, update_price…)             |
| `product_id`     | ID do produto                                              |
| `product_sku`    | SKU do produto afetado                                     |
| `payload`        | Dados recebidos no job                                     |
| `status`         | `pending`, `success`, `failed`, `retried` ou `duplicated`  |
| `error_message`  | Mensagem de erro (quando `status = failed`)                |
| `sqs_message_id` | ID da mensagem SQS para deduplicação                       |

---
## Task Scheduling

O container `scheduler` executa tarefas agendadas via `php artisan schedule:run` a cada 60 segundos.

| Comando             | Frequência  | Descrição                                            |
|---------------------|-------------|------------------------------------------------------|
| `jobs:retry-failed` | A cada hora | Reprocessa jobs com `status = failed` da última hora |

---

## Queue Worker

O container `queue` fica em execução contínua consumindo a fila SQS:

```bash
php artisan queue:work sqs --queue=product-hub-queue --tries=3
```

As mensagens recebidas são roteadas pelo `SqsJobDispatcher` conforme o `job_type`.

---

## Jobs suportados

| `job_type`           | Descrição                     |
|----------------------|-------------------------------|
| `update_stock`       | Atualiza o estoque do produto |
| `update_price`       | Atualiza o preço              |
| `update_description` | Atualiza a descrição          |
| `update_images`      | Adiciona imagens              |
| `update_tags`        | Adiciona tags                 |

---

## Exemplos de requisições
### Criar produto

```bash
curl -X POST http://localhost:8080/api/v1/products \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "sku": "SKU-001",
    "name": "Produto Exemplo",
    "description": "Descrição do produto",
    "price": 99.90,
    "stock": 100
  }'
```

**Resposta 201:**
```json
{
  "message": "Produto cadastrado com sucesso",
  "payload": {
    "id": 1,
    "sku": "SKU-001",
    "name": "Produto Exemplo",
    "price": "99.90",
    "stock": 100,
    "created_at": "2026-05-10T12:54:05.000000Z"
  }
}
```

### Publicar mensagem SQS

```bash
# Atualizar estoque
curl -X POST http://localhost:8080/api/v1/sqs/publish \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "job_type": "update_stock",
    "product_sku": "SKU-001",
    "data": { "stock": 50 }
  }'

# Atualizar preço
curl -X POST http://localhost:8080/api/v1/sqs/publish \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "job_type": "update_price",
    "product_sku": "SKU-001",
    "data": { "price": 149.90 }
  }'

# Atualizar descrição
curl -X POST http://localhost:8080/api/v1/sqs/publish \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "job_type": "update_description",
    "product_sku": "SKU-001",
    "data": { "description": "Nova descrição do produto" }
  }'

# Adicionar imagens
curl -X POST http://localhost:8080/api/v1/sqs/publish \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "job_type": "update_images",
    "product_sku": "SKU-001",
    "data": { "images": ["https://cdn.example.com/img1.jpg", "https://cdn.example.com/img2.jpg"] }
  }'

# Adicionar tags
curl -X POST http://localhost:8080/api/v1/sqs/publish \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "job_type": "update_tags",
    "product_sku": "SKU-001",
    "data": { "tags": ["promo", "destaque"] }
  }'
```

**Resposta 201:**
```json
{
  "message": "Mensagem publicada na fila SQS",
  "payload": {
    "job_type": "update_stock",
    "product_sku": "SKU-001",
    "data": { ... }
  }
}
```
