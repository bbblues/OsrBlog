<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use PDOStatement;

/**
 * Acesso aos dados da tabela comments.
 */
final class CommentRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    // Insere um comentário e retorna o id gerado.
    public function insert(array $data): int
    {
        $sql = 'INSERT INTO comments (post_id, author_name, body, status, ip_hash, created_at)
                VALUES (:post_id, :author_name, :body, :status, :ip_hash, NOW())';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    // Lista comentários publicados de um post (ordem ASC).
    public function findPublishedByPost(int $postId): array
    {
        $sql = 'SELECT id, post_id, author_name, body, status, created_at
                FROM comments
                WHERE post_id = :post_id AND status = :status
                ORDER BY created_at ASC, id ASC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['post_id' => $postId, 'status' => 'published']);

        return $stmt->fetchAll();
    }

    // Conta comentários recentes de um IP (rate limit de 5/hora).
    public function countRecentByIpHash(string $ipHash): int
    {
        $sql = 'SELECT COUNT(*)
                FROM comments
                WHERE ip_hash = :ip_hash AND created_at > (NOW() - INTERVAL 1 HOUR)';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['ip_hash' => $ipHash]);

        return (int) $stmt->fetchColumn();
    }

    // ------------------------------------------------------------------
    // Admin
    // ------------------------------------------------------------------

    // Lista comentários com filtros (status, post e sinalizados).
    public function findWithFilters(?string $status, ?int $postId, bool $flagged, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildFilters($status, $postId, $flagged);

        $sql = 'SELECT c.id, c.post_id, c.author_name, c.body, c.status, c.ip_hash, c.created_at,
                       p.title AS post_title, p.slug AS post_slug
                FROM comments c
                INNER JOIN posts p ON p.id = c.post_id';

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.created_at DESC, c.id DESC LIMIT :limit OFFSET :offset';
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $this->bind($stmt, $params);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countWithFilters(?string $status, ?int $postId, bool $flagged): int
    {
        [$where, $params] = $this->buildFilters($status, $postId, $flagged);

        $sql = 'SELECT COUNT(*) FROM comments c';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function findById(int $id): ?array
    {
        $sql = 'SELECT id, post_id, author_name, body, status, ip_hash, created_at
                FROM comments WHERE id = :id LIMIT 1';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = $this->pdo->prepare('UPDATE comments SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM comments WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    // Atualiza o status de vários comentários em uma única transação (atômico).
    public function batchUpdateStatus(array $ids, string $status): void
    {
        if ($ids === []) {
            return;
        }

        $this->pdo->beginTransaction();

        try {
            $stmt = $this->pdo->prepare('UPDATE comments SET status = :status WHERE id = :id');
            foreach ($ids as $id) {
                $stmt->execute(['status' => $status, 'id' => $id]);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();

            throw $e;
        }
    }

    // IPs e autores já marcados como spam (para badges de sinalização).
    public function spamIpHashes(): array
    {
        $rows = $this->pdo->query("SELECT DISTINCT ip_hash FROM comments WHERE status = 'spam' AND ip_hash IS NOT NULL")->fetchAll();

        return array_column($rows, 'ip_hash');
    }

    public function spamAuthors(): array
    {
        $rows = $this->pdo->query("SELECT DISTINCT author_name FROM comments WHERE status = 'spam'")->fetchAll();

        return array_column($rows, 'author_name');
    }

    private function buildFilters(?string $status, ?int $postId, bool $flagged): array
    {
        $where = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'c.status = :status';
            $params['status'] = $status;
        }

        if ($postId !== null) {
            $where[] = 'c.post_id = :post_id';
            $params['post_id'] = $postId;
        }

        if ($flagged) {
            $where[] = "(c.status != 'spam' AND (c.ip_hash IN (SELECT DISTINCT ip_hash FROM comments WHERE status = 'spam' AND ip_hash IS NOT NULL) OR c.author_name IN (SELECT DISTINCT author_name FROM comments WHERE status = 'spam')))";
        }

        return [$where, $params];
    }

    private function bind(PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(':' . $key, $value, $type);
        }
    }
}
