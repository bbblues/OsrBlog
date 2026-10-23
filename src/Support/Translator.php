<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Traduz posts do português para o inglês usando a API de chat do DeepSeek.
 * A comunicação é feita exclusivamente via cURL.
 */
final class Translator
{
    private const API_URL = 'https://api.deepseek.com/chat/completions';
    private const MODEL = 'deepseek-chat';
    private const TEMPERATURE = 0.3;
    private const TIMEOUT_SECONDS = 60;
    private const CONNECT_TIMEOUT_SECONDS = 10;

    /**
     * Traduz título, resumo e corpo para o inglês.
     * Retorna ['title', 'summary', 'body_md'] em caso de sucesso, ou null em qualquer falha.
     */
    public function translatePost(string $title, string $summary, string $bodyMd): ?array
    {
        $apiKey = $this->getApiKey();
        if ($apiKey === '') {
            return null;
        }

        $payload = [
            'model' => self::MODEL,
            'temperature' => self::TEMPERATURE,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional translator from Portuguese to English. '
                        . 'Translate the provided fields while preserving all Markdown formatting exactly. '
                        . 'Return STRICTLY a single JSON object (no markdown fences, no backticks, no extra text) '
                        . 'with exactly these keys: "title", "summary" and "body_md".',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'title' => $title,
                        'summary' => $summary,
                        'body_md' => $bodyMd,
                    ], JSON_UNESCAPED_UNICODE),
                ],
            ],
        ];

        $response = $this->post($payload);
        if ($response === null) {
            return null;
        }

        return $this->extractTranslation($response);
    }

    // ------------------------------------------------------------------
    // Helpers internos
    // ------------------------------------------------------------------

    // Faz o POST na API e devolve a resposta decodificada, ou null em caso de erro.
    private function post(array $payload): ?array
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init(self::API_URL);
        if ($ch === false) {
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getApiKey(),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
        ]);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($body === false) {
            return null;
        }

        if ($status < 200 || $status >= 300) {
            return null;
        }

        $decoded = json_decode((string) $body, true);

        return is_array($decoded) ? $decoded : null;
    }

    // Extrai o JSON retornado pela IA do campo content e valida as chaves esperadas.
    private function extractTranslation(array $response): ?array
    {
        $content = $response['choices'][0]['message']['content'] ?? null;
        if (!is_string($content)) {
            return null;
        }

        // Remove cercas de markdown que a IA possa insistir em retornar.
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content);
        $content = preg_replace('/\s*```$/', '', $content);

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            return null;
        }

        // Valida se todas as chaves esperadas existem e são strings.
        foreach (['title', 'summary', 'body_md'] as $key) {
            if (!isset($decoded[$key]) || !is_string($decoded[$key])) {
                return null;
            }
        }

        return [
            'title' => $decoded['title'],
            'summary' => $decoded['summary'],
            'body_md' => $decoded['body_md'],
        ];
    }

    // Lê a chave da API da constante definida no config.php.
    private function getApiKey(): string
    {
        $key = defined('DEEPSEEK_API_KEY') ? DEEPSEEK_API_KEY : '';

        return is_string($key) ? trim($key) : '';
    }
}
