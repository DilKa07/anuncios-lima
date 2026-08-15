<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
    <h2>Gestion de anuncios</h2>
    <?php $isAdminView = is_admin_user($conexion ?? null); ?>
    <?php $currentUserId = (int)($_SESSION['user_id'] ?? 0); ?>
    <?php $searchTerm = trim((string)($_GET['q'] ?? '')); ?>
    <?php $autoDeleteConfig = $autoDeleteConfig ?? ['free_days' => 30]; ?>
    <?php
        $formatRemaining = static function ($dateValue) {
            $ts = strtotime((string)$dateValue);
            if (!$ts) {
                return '';
            }
            $diff = $ts - time();
            if ($diff <= 0) {
                return 'vencido';
            }

            $hours = (int)floor($diff / 3600);
            $days = (int)floor($hours / 24);
            $restHours = $hours % 24;

            if ($days > 0) {
                return 'faltan ' . $days . 'd ' . $restHours . 'h';
            }
            return 'faltan ' . max(1, $hours) . 'h';
        };
    ?>

    <?php if ($isAdminView): ?>
        <div class="card" style="margin:14px 0 16px;padding:14px;border-radius:12px;border:1px solid #eadfce;background:#fff8ef;display:grid;gap:12px;">
            <form method="get" action="/mis-anuncios" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <label for="admin-search-anuncios" style="font-weight:700;">Buscar anuncio</label>
                <input id="admin-search-anuncios" type="text" name="q" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Titulo, empresa, telefono o usuario" style="min-width:320px;flex:1;padding:10px;border-radius:10px;border:1px solid #d8c8b5;background:#fff;">
                <button type="submit" class="btn-primary" style="min-height:38px;padding:0 12px;">Buscar</button>
                <?php if ($searchTerm !== ''): ?>
                    <a href="/mis-anuncios" class="btn-outline" style="min-height:38px;padding:0 12px;">Limpiar</a>
                <?php endif; ?>
            </form>

            <form method="post" action="/mis-anuncios" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="update_auto_delete_config">

                <label for="free-days" style="font-weight:700;">Vigencia gratuitos</label>
                <select id="free-days" name="free_days" style="min-width:140px;padding:10px;border-radius:10px;border:1px solid #d8c8b5;background:#fff;">
                    <?php foreach ([7,15,30,45,60,90,120,180,365] as $d): ?>
                        <option value="<?php echo $d; ?>" <?php echo ((int)$autoDeleteConfig['free_days'] === $d) ? 'selected' : ''; ?>><?php echo $d; ?> dias</option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-primary" style="min-height:38px;padding:0 12px;">Guardar reglas</button>
            </form>
            <p style="font-size:13px;color:var(--muted);margin:0;">Los anuncios gratuitos usan esta regla global. Los destacados pendientes se aprueban con vigencia individual.</p>
        </div>
    <?php endif; ?>

    <?php if (is_admin_user($conexion ?? null) && !empty($pendientesDestacados)): ?>
        <div class="card" style="margin:14px 0 16px;padding:14px;border-radius:12px;border:1px solid #eadfce;background:#fff8ef;">
            <h3 style="margin-bottom:10px;">Anuncios destacados pendientes de aprobación</h3>
            <ul style="list-style:none;padding:0;margin:0;display:grid;gap:8px;">
                <?php foreach($pendientesDestacados as $p): ?>
                    <li style="display:flex;justify-content:space-between;gap:10px;align-items:center;padding:10px;border:1px solid #eadfce;border-radius:10px;background:#fff;">
                        <div>
                            <strong><?php echo htmlspecialchars($p['titulo']); ?></strong>
                            <div style="font-size:13px;color:var(--muted);">Usuario: <?php echo htmlspecialchars($p['usuario_nombre']); ?> | Estado: <?php echo htmlspecialchars($p['estado']); ?></div>
                        </div>
                        <form method="post" action="/aprobar-destacado" style="margin:0;display:flex;gap:8px;align-items:center;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                            <select name="featured_days" style="min-width:120px;padding:8px;border-radius:10px;border:1px solid #d8c8b5;background:#fff;">
                                <?php foreach ([7,15,30,45,60,90,120,180,365] as $d): ?>
                                    <option value="<?php echo $d; ?>" <?php echo ($d === 30) ? 'selected' : ''; ?>><?php echo $d; ?> dias</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-primary" style="min-height:36px;padding:0 12px;">Aprobar y publicar</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (empty($mis)) : ?>
        <p><?php echo $isAdminView ? 'No hay anuncios para gestionar.' : 'No tienes anuncios publicados.'; ?></p>
    <?php else: ?>
        <ul style="list-style:none;padding:0;">
            <?php foreach($mis as $m): ?>
                <li class="card" style="margin-bottom:10px;padding:12px;display:flex;gap:12px;align-items:center;">
                    <div style="width:120px;height:72px;flex:0 0 120px">
                        <?php
                            if (!empty($m['imagen']) && is_file(__DIR__ . '/../../' . $m['imagen'])) {
                                $thumb = BASE_PATH . '/uploads/empleos/thumb_' . basename($m['imagen']);
                                $imgToShow = is_file(__DIR__ . '/../../uploads/empleos/thumb_' . basename($m['imagen'])) ? $thumb : (BASE_PATH . '/' . ltrim($m['imagen'], '/'));
                            } else {
                                $imgToShow = null;
                            }
                        ?>
                        <?php if ($imgToShow): ?>
                            <img src="<?php echo htmlspecialchars($imgToShow); ?>" style="width:120px;height:72px;object-fit:cover;border-radius:6px;border:1px solid rgba(15,23,42,0.04)" alt="mini">
                        <?php else: ?>
                            <div style="width:120px;height:72px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--muted)">Sin imagen</div>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1">
                        <strong><?php echo htmlspecialchars($m['titulo']); ?></strong>
                        <div style="font-size:13px;color:var(--muted);">Estado: <?php echo htmlspecialchars($m['estado']); ?> | Destacado: <?php echo $m['destacado'] ? 'Sí' : 'No'; ?></div>
                        <?php if ($isAdminView): ?>
                            <div style="font-size:13px;color:var(--muted);">Publicado por: <?php echo htmlspecialchars($m['usuario_nombre'] ?? ('ID ' . (int)($m['usuario_id'] ?? 0))); ?></div>
                            <?php if ((int)($m['destacado'] ?? 0) === 1): ?>
                                <?php $expTs = !empty($m['fecha_expiracion_destacado']) ? strtotime((string)$m['fecha_expiracion_destacado']) : false; ?>
                                <?php if ($expTs): ?>
                                    <div style="font-size:13px;color:var(--muted);">Vence: <?php echo date('d/m/Y H:i', $expTs); ?> (<?php echo htmlspecialchars($formatRemaining($m['fecha_expiracion_destacado'])); ?>)</div>
                                <?php else: ?>
                                    <div style="font-size:13px;color:var(--muted);">Vigencia de destacado pendiente de definicion.</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <div style="flex:0 0 auto;display:flex;gap:8px;align-items:center;">
                        <?php if ($isAdminView || (int)($m['usuario_id'] ?? 0) === $currentUserId): ?>
                            <a class="btn-outline" href="/editar-empleo?id=<?php echo $m['id']; ?>">Editar</a>
                        <?php endif; ?>
                        <form method="post" action="/eliminar-empleo" onsubmit="return confirm('¿Seguro que deseas eliminar este anuncio?');" style="margin:0;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" value="<?php echo (int)$m['id']; ?>">
                            <button type="submit" class="btn-primary" style="min-height:38px;padding:0 12px;background:#b42318;box-shadow:none;"><?php echo $isAdminView ? 'Eliminar (admin)' : 'Eliminar'; ?></button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
