# osr.blog.br

Blog pessoal sobre RPG analógico (movimento OSR).

## Stack

- PHP 8.4 (sem framework)
- MySQL 8.0
- Composer (league/commonmark, vlucas/phpdotenv, cocur/slugify)
- Bootstrap 5 via CDN
- Docker (php:8.4-apache + mysql:8.0)

## Requisitos

- Docker e Docker Compose.

## Executando localmente (Fase 0)

1. (Opcional) Crie o arquivo `.env` a partir do exemplo: `cp .env.example .env`.
2. Suba os contêineres: `docker compose up --build`.
3. Acesse <http://localhost:8080> — a resposta deve ser `OK`.
