<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Traduções estáticas da interface pública.
 * Cada chave aponta para ['pt' => ..., 'en' => ...].
 */
final class Lang
{
    private const TRANSLATIONS = [
        // Home.
        'home.recent_posts' => ['pt' => 'Últimos posts', 'en' => 'Recent posts'],
        'home.no_posts' => ['pt' => 'Nenhum post publicado ainda.', 'en' => 'No posts published yet.'],
        'home.description' => ['pt' => 'Blog pessoal sobre RPG analógico (movimento OSR).', 'en' => 'Personal blog about tabletop RPGs (OSR movement).'],

        // Post.
        'post.comments' => ['pt' => 'Comentários', 'en' => 'Comments'],
        'post.published_in' => ['pt' => 'Publicado em', 'en' => 'Published on'],
        'post.no_comments' => ['pt' => 'Seja o primeiro a comentar.', 'en' => 'Be the first to comment.'],

        // Comentários.
        'comment.leave_comment' => ['pt' => 'Deixe um comentário', 'en' => 'Leave a comment'],
        'comment.name' => ['pt' => 'Nome', 'en' => 'Name'],
        'comment.comment' => ['pt' => 'Comentário', 'en' => 'Comment'],
        'comment.submit' => ['pt' => 'Enviar comentário', 'en' => 'Post comment'],
        'comment.website' => ['pt' => 'Website (não preencha)', 'en' => 'Website (do not fill in)'],

        // Busca.
        'search.title' => ['pt' => 'Busca', 'en' => 'Search'],
        'search.placeholder' => ['pt' => 'Buscar posts...', 'en' => 'Search posts...'],
        'search.button' => ['pt' => 'Buscar', 'en' => 'Search'],
        'search.prompt' => ['pt' => 'Digite um termo para buscar.', 'en' => 'Type a term to search.'],
        'search.no_results' => ['pt' => 'Nenhum resultado para', 'en' => 'No results for'],
        'search.results' => ['pt' => 'resultado(s) para', 'en' => 'result(s) for'],
        'search.description' => ['pt' => 'Busca no blog.', 'en' => 'Search the blog.'],
        'search.description_results' => ['pt' => 'Resultados da busca para', 'en' => 'Search results for'],

        // Categoria.
        'category.title' => ['pt' => 'Categoria', 'en' => 'Category'],
        'category.no_posts' => ['pt' => 'Nenhum post publicado nesta categoria.', 'en' => 'No posts published in this category.'],
        'category.description' => ['pt' => 'Posts publicados na categoria', 'en' => 'Posts published in the category'],

        // Paginação.
        'pagination.label' => ['pt' => 'Paginação', 'en' => 'Pagination'],
        'pagination.previous' => ['pt' => 'Anterior', 'en' => 'Previous'],
        'pagination.next' => ['pt' => 'Próxima', 'en' => 'Next'],
        'lang.switcher' => ['pt' => 'Idioma', 'en' => 'Language'],
        'common.page' => ['pt' => 'Página', 'en' => 'Page'],

        // Erros.
        'error.404' => ['pt' => 'Página não encontrada', 'en' => 'Page not found'],
        'error.home' => ['pt' => 'Voltar para a home', 'en' => 'Back to home'],
    ];

    // Classe estática: não deve ser instanciada.
    private function __construct()
    {
    }

    /**
     * Retorna a tradução da chave no idioma informado.
     * Fallback: chave inexistente -> retorna a própria chave;
     * idioma sem tradução -> retorna o texto em português.
     */
    public static function get(string $key, string $lang = 'pt'): string
    {
        $translations = self::TRANSLATIONS[$key] ?? null;

        if ($translations === null) {
            return $key;
        }

        $lang = $lang === 'en' ? 'en' : 'pt';

        return $translations[$lang] ?? $translations['pt'] ?? $key;
    }
}
