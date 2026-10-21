<?php

// Componente de meta tags para SEO (incluído no <head> das views).

$appUrl = rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost:8080'), '/');
$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');

// URL canônica self-referencing (aponta para a própria página atual).
$canonical = $appUrl . $requestUri;

$pageTitle = (string) ($title ?? 'osr.blog.br');
$metaDescription = (string) ($description ?? 'Blog pessoal sobre RPG analógico (movimento OSR).');
$ogImage = (string) ($ogImage ?? '');

if ($ogImage !== '' && str_starts_with($ogImage, '/')) {
    $ogImage = $appUrl . $ogImage;
}
?>
<title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
<?php if ($ogImage !== ''): ?>
<meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
