<!DOCTYPE html>
<html lang="es">
<head>
    <?php
    $cfg = require __DIR__ . '/../../../config/app.php';
    $seoCfg = $cfg['seo'] ?? [];
    $siteName = $cfg['app_name'] ?? 'Anuncios Lima';
    $appUrl = rtrim((string)($cfg['app_url'] ?? ''), '/');
    $host = (string)($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if ($appUrl === '' || strpos(strtolower($appUrl), 'localhost') !== false || strpos($appUrl, '127.0.0.1') !== false) {
        $appUrl = $scheme . '://' . $host;
    }
    $requestPath = $_SERVER['REQUEST_URI'] ?? '';
    $canonicalPath = strtok($requestPath, '?');
    $canonicalUrl = (strpos($canonicalPath, 'http') === 0) ? $canonicalPath : ($appUrl . $canonicalPath);
    $logoPath = '/public/assets/images/logo_anuncios.jpg';
    $logoFile = __DIR__ . '/../../../public/assets/images/logo_anuncios.jpg';
    $logoVersion = is_file($logoFile) ? (string)filemtime($logoFile) : '20260709-1';
    $logoUrl = $appUrl . $logoPath . '?v=' . rawurlencode($logoVersion);
    $pageTitle = $pageTitle ?? ($seoCfg['default_title'] ?? ($siteName . ' | Portal de empleos'));
    $pageDescription = $pageDescription ?? ($seoCfg['default_description'] ?? 'Portal de anuncios y bolsa de trabajo.');
    $googleVerify = trim((string)($seoCfg['google_site_verification'] ?? ''));
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($siteName); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonicalUrl); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($logoUrl); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($pageTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($pageDescription); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($logoUrl); ?>">
    <?php
    $cssFile = __DIR__ . '/../../../public/assets/css/style.css';
    $cssVersion = is_file($cssFile) ? (string)filemtime($cssFile) : '20260709-1';
    ?>
    <link rel="icon" href="<?php echo htmlspecialchars($logoPath); ?>?v=<?php echo urlencode($logoVersion); ?>">
    <link rel="icon" type="image/jpeg" sizes="48x48" href="<?php echo htmlspecialchars($logoPath); ?>?v=<?php echo urlencode($logoVersion); ?>">
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($logoPath); ?>?v=<?php echo urlencode($logoVersion); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($logoPath); ?>?v=<?php echo urlencode($logoVersion); ?>">
    <?php if ($googleVerify !== ''): ?>
        <meta name="google-site-verification" content="<?php echo htmlspecialchars($googleVerify); ?>">
    <?php endif; ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": <?php echo json_encode($siteName, JSON_UNESCAPED_UNICODE); ?>,
            "url": <?php echo json_encode($appUrl, JSON_UNESCAPED_UNICODE); ?>,
            "logo": <?php echo json_encode($logoUrl, JSON_UNESCAPED_UNICODE); ?>
    }
    </script>
    <link rel="stylesheet" href="/public/assets/css/style.css?v=<?php echo urlencode($cssVersion); ?>">
</head>
<?php
    $isHome = false;
    if (isset($_GET['url'])) {
        $isHome = empty($_GET['url']) || $_GET['url'] === 'home' || $_GET['url'] === '/';
    } else {
        // when no url param, assume homepage
        $isHome = true;
    }
?>
<body class="<?php echo $isHome ? 'home' : ''; ?>">

<header>
    <div class="header-inner">
        <div class="brand">
            <a href="/" aria-label="Anuncios Lima"><img src="/public/assets/images/logo_anuncios.jpg" alt="Anuncios Lima" class="brand-logo"></a>
            <div class="brand-copy">
                <h1>Anuncios Lima</h1>
                <span>Clasificados y bolsa de trabajo</span>
            </div>
        </div>

        <a href="/publicar-empleo" class="btn-primary nav-mobile-cta">Publicar anuncio</a>

        <button type="button" class="nav-mobile-toggle" id="nav-mobile-toggle" aria-label="Abrir menu" aria-controls="site-nav" aria-expanded="false">MENU</button>

        <nav id="site-nav" class="site-nav" aria-label="Principal">
            <a href="/">Inicio</a>
            <a href="/empleos">Empleos</a>
            <a href="/publicar-empleo" class="btn-primary nav-cta">Publicar anuncio</a>
            <?php if (!empty(
                
                
                $_SESSION['user_id'])): ?>
                <details class="nav-user-menu">
                    <summary class="nav-user">Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></summary>
                    <div class="nav-user-dropdown" role="menu">
                        <a href="/mis-anuncios" role="menuitem">Gestion de anuncios</a>
                        <?php if (($_SESSION['user_role'] ?? '') === 'admin'): ?>
                            <a href="/gestion-lugares" role="menuitem">Gestion de lugar</a>
                            <a href="/gestion-usuarios" role="menuitem">Gestion de usuarios</a>
                        <?php endif; ?>
                        <a href="/mi-cuenta" role="menuitem">Ajuste de usuario</a>
                        <a href="/logout" role="menuitem">Cerrar sesion</a>
                    </div>
                </details>
            <?php else: ?>
                <a href="/login">Iniciar Sesión</a>
                <a href="/registro">Registrarse</a>
            <?php endif; ?>
        </nav>
    </div>

    <div class="container">
        <?php if (!empty($_SESSION['flash_success'])): ?>
            <div class="flash flash-success"><?php echo htmlspecialchars($_SESSION['flash_success']); unset($_SESSION['flash_success']); ?></div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['flash_error'])): ?>
            <div class="flash flash-error"><?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
        <?php endif; ?>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('nav-mobile-toggle');
    var nav = document.getElementById('site-nav');
    if (!toggle || !nav) return;

    function closeMobileNav() {
        nav.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function () {
        var isOpen = nav.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    nav.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.matchMedia('(max-width: 980px)').matches) {
                closeMobileNav();
            }
        });
    });

    window.addEventListener('resize', function () {
        if (!window.matchMedia('(max-width: 980px)').matches) {
            closeMobileNav();
        }
    });
});
</script>