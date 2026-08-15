<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
    <div class="section-title">
        <div>
            <span class="section-kicker">Publicidad</span>
            <h3>Todas las publicidades activas</h3>
        </div>
        <a class="btn-outline" href="/">Volver al inicio</a>
    </div>

    <?php if (!empty($publicidades)): ?>
        <section class="home-sponsor-older" aria-label="Listado de publicidades">
            <?php foreach ($publicidades as $pub): ?>
                <?php
                    $itemImg = $pub['imagen'] ?? '';
                    $itemStyle = '';
                    if (!empty($itemImg)) {
                        $itemStyle = " style=\"--ad-bg:url('" . htmlspecialchars(BASE_PATH . '/' . ltrim($itemImg, '/')) . "');\"";
                    }
                ?>
                <a class="home-sponsor-hero home-sponsor-hero-stacked" href="<?php echo htmlspecialchars($pub['enlace'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer"<?php echo $itemStyle; ?>>
                    <div class="home-sponsor-copy">
                        <?php $itemTitle = trim((string)($pub['titulo'] ?? '')); ?>
                        <?php $itemDesc = trim((string)($pub['descripcion'] ?? '')); ?>
                        <?php if ($itemTitle !== ''): ?>
                            <h3><?php echo htmlspecialchars($itemTitle); ?></h3>
                        <?php endif; ?>
                        <?php if ($itemDesc !== ''): ?>
                            <p><?php echo htmlspecialchars(mb_strimwidth($itemDesc, 0, 170, '...')); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </section>
    <?php else: ?>
        <div class="empty-state">
            <h3>No hay publicidades activas</h3>
            <p>Por ahora no hay más publicidades para mostrar.</p>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
