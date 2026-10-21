<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Valida e salva imagens enviadas via upload.
 */
final class ImageUpload
{
    /**
     * Tipos MIME permitidos e suas extensões.
     *
     * @var array<string, string>
     */
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(private string $uploadDir)
    {
    }

    // Valida e salva a imagem; retorna o nome do arquivo gerado.
    public function store(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Falha no envio do arquivo.');
        }

        $tmpPath = $file['tmp_name'] ?? '';
        if (!is_string($tmpPath) || !is_uploaded_file($tmpPath)) {
            throw new \RuntimeException('Arquivo de upload inválido.');
        }

        // Verifica o MIME real do conteúdo (não confia na extensão).
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmpPath);

        if (!is_string($mime) || !isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Tipo de imagem não permitido. Use JPEG, PNG ou WebP.');
        }

        $filename = bin2hex(random_bytes(16)) . '.' . self::ALLOWED[$mime];

        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        $destination = rtrim($this->uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmpPath, $destination)) {
            throw new \RuntimeException('Não foi possível salvar a imagem.');
        }

        return $filename;
    }
}
