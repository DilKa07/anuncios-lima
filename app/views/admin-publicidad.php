<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
    <section class="admin-pub-page">
        <div class="section-title">
            <div>
                <span class="section-kicker">Panel admin</span>
                <h3>Agregar nueva publicidad</h3>
            </div>
            <a class="btn-outline" href="/">Volver al inicio</a>
        </div>

        <form action="/admin-publicidad-guardar" method="post" enctype="multipart/form-data" class="home-sponsor-admin-form">
            <?php echo csrf_field(); ?>
            <div class="admin-grid">
                <label>
                    Titulo
                    <input type="text" name="titulo" maxlength="180">
                </label>
                <label>
                    Enlace
                    <input type="url" name="enlace" placeholder="https://ejemplo.com" required>
                </label>
            </div>
            <label>
                Descripcion
                <textarea name="descripcion" rows="3" maxlength="2000"></textarea>
            </label>
            <label>
                Imagen (opcional: JPG, PNG o WEBP)
                <input type="file" name="imagen" accept="image/png,image/jpeg,image/webp">
            </label>
            <button type="submit" class="btn-primary">Guardar publicidad</button>
        </form>

        <?php if (!empty($publicidadEditar)): ?>
            <hr style="margin:20px 0;border:none;border-top:1px solid #e5d8c5;">
            <div class="section-title" style="margin-bottom:10px;">
                <div>
                    <span class="section-kicker">Edición</span>
                    <h3>Editar publicidad</h3>
                </div>
                <a class="btn-outline" href="/admin-publicidad">Cancelar edición</a>
            </div>

            <form action="/admin-publicidad-actualizar" method="post" enctype="multipart/form-data" class="home-sponsor-admin-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="id" value="<?php echo (int)$publicidadEditar['id']; ?>">
                <div class="admin-grid">
                    <label>
                        Titulo
                        <input type="text" name="titulo" maxlength="180" value="<?php echo htmlspecialchars($publicidadEditar['titulo'] ?? ''); ?>">
                    </label>
                    <label>
                        Enlace
                        <input type="url" name="enlace" required value="<?php echo htmlspecialchars($publicidadEditar['enlace'] ?? ''); ?>">
                    </label>
                </div>
                <label>
                    Descripcion
                    <textarea name="descripcion" rows="3" maxlength="2000"><?php echo htmlspecialchars($publicidadEditar['descripcion'] ?? ''); ?></textarea>
                </label>
                <label>
                    Reemplazar imagen (opcional)
                    <input type="file" name="imagen" accept="image/png,image/jpeg,image/webp">
                </label>
                <button type="submit" class="btn-primary">Guardar cambios</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="admin-pub-list">
        <div class="section-title">
            <div>
                <span class="section-kicker">Publicidades</span>
                <h3>Historial (mas reciente primero)</h3>
            </div>
        </div>

        <?php if (!empty($publicidades)): ?>
            <div class="home-sponsor-older-grid">
                <?php foreach ($publicidades as $pub): ?>
                    <?php
                        $img = $pub['imagen'] ?? '';
                        $imgSrc = !empty($img) ? (BASE_PATH . '/' . ltrim($img, '/')) : '';
                    ?>
                    <article class="home-sponsor-older-item">
                        <?php if (!empty($imgSrc)): ?>
                            <div class="older-media"><img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($pub['titulo'] ?? 'Publicidad'); ?>"></div>
                        <?php endif; ?>
                        <div class="older-body">
                            <h4><?php echo htmlspecialchars($pub['titulo'] ?? 'Publicidad'); ?></h4>
                            <p><?php echo htmlspecialchars(mb_strimwidth($pub['descripcion'] ?? '', 0, 120, '...')); ?></p>
                            <a href="<?php echo htmlspecialchars($pub['enlace'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer">Ir al enlace</a>
                            <div class="older-actions">
                                <a class="btn-outline" href="/admin-publicidad?edit_id=<?php echo (int)($pub['id'] ?? 0); ?>">Editar</a>
                                <form method="post" action="/admin-publicidad-eliminar" onsubmit="return confirm('¿Eliminar esta publicidad?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id" value="<?php echo (int)($pub['id'] ?? 0); ?>">
                                    <button class="btn-primary" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h3>Aun no tienes publicidades</h3>
                <p>Crea la primera publicidad usando el formulario superior.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
