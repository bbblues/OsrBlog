# osr.blog.br

Blog pessoal sobre RPG analógico, com foco no movimento OSR (Old School Renaissance).

Este projeto foi construído do zero utilizando o padrão MVC (Model-View-Controller) em PHP puro, sem frameworks robustos, com foco em segurança, performance e simplicidade.

## Tecnologias Utilizadas

- **Backend:** PHP 8.4 (PHP puro, arquitetura MVC)
- **Banco de Dados:** MySQL 8.0
- **Frontend:** Bootstrap 5 (via CDN), HTML5, CSS3, JavaScript
- **Editor de Texto:** Cherry Markdown (suporte a conversão de HTML do Notion para Markdown)
- **Gerenciamento de Dependências:** Composer
- **Ambiente de Desenvolvimento:** Docker e Docker Compose

## Funcionalidades

- **Área Pública:** Listagem de posts paginada, busca, categorias, leitura de posts com Markdown renderizado.
- **Área Administrativa:** Login seguro, CRUD de posts, CRUD de categorias, upload de imagens (com validação de MIME).
- **Comentários:** Sistema anônimo com moderação pós-publicação, honeypot anti-bot, bloqueio de links e rate limit (5 por hora).
- **SEO:** Sitemap.xml dinâmico, Feed RSS, Meta tags Open Graph automáticas.
- **Analytics Caseiro:** Registro de visitas em arquivos TSV mensais, com IP hasheado (em conformidade com a LGPD), sem uso de cookies.
- **Backup:** Geração de arquivo `.zip` sob demanda contendo dump do banco, imagens e logs.

## Segurança

O projeto implementa camadas de segurança de ponta a ponta:
- PDO com Prepared Statements (Prevenção de SQL Injection).
- Escape de saída com `htmlspecialchars` (Prevenção de XSS).
- Tokens CSRF em todos os formulários POST.
- Sessões seguras com cookies `HttpOnly` e `SameSite=Strict`.
- Senhas armazenadas com `password_hash` (Bcrypt).
- Pasta `storage/uploads/` protegida contra execução de scripts.

## Como Rodar o Projeto Localmente

### Pré-requisitos
- Docker e Docker Compose instalados.
- Git (opcional).

### Passo a Passo

1. Clone o repositório ou baixe os arquivos.
2. Na raiz do projeto, crie o arquivo de variáveis de ambiente:
    
    cp .env.example .env
    
3. Suba os containers Docker:
    
    docker compose up -d --build
    
4. Importe o schema e os dados iniciais (Windows/PowerShell):
    
    cmd /c "type database\schema.sql | docker compose exec -T mysql mysql -uosr -pchangeme osr_blog"
    cmd /c "type database\seed.sql | docker compose exec -T mysql mysql -uosr -pchangeme osr_blog"
    
   *(Em Linux/Mac, substitua o comando `type` por `cat`)*

5. Acesse o site:
   - Público: `http://localhost:8080`
   - Admin: `http://localhost:8080/admin/login`

### Credenciais de Teste (Seed)
- **Usuário:** `admin`
- **Senha:** `admin123`

## Implantação (Produção)

Para instruções detalhadas de como subir o projeto para uma hospedagem compartilhada (cPanel/Inghost) via FTP, leia o arquivo [DEPLOY.md](DEPLOY.md) na raiz do projeto.

## Licença

Este é um projeto pessoal de código aberto. Sinta-se livre para estudar a estrutura e adaptar para seus próprios projetos.