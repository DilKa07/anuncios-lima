<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">

    <?php
    $activeCount = isset($empleos) && is_array($empleos) ? count($empleos) : 0;
    $recentCount = isset($recientes) && is_array($recientes) ? count($recientes) : 0;
    $isAdmin = function_exists('is_admin_user') ? is_admin_user($conexion ?? null) : false;
    $featuredPublicidad = !empty($publicidades) ? $publicidades[0] : null;
    $olderPublicidades = !empty($publicidades) && is_array($publicidades) ? array_slice($publicidades, 1, 3) : [];
    ?>

    <section class="hero portal-hero">
        <div class="hero-bg">
            <img src="/public/assets/images/Portada.jpeg" alt="Portada Anuncios Lima">
        </div>
        <div class="hero-inner portal-hero-inner">
            <div class="hero-copy">
                <span class="eyebrow">Portal de clasificados laborales</span>
                <h2>Miles de oportunidades laborales en Lima</h2>
                <p>Publica gratis, destaca tus anuncios y conecta con postulantes sin intermediarios en un formato simple y directo.</p>
            </div>

            <asideass="hero-aside">
                <div class="search-box portal-search-box">
                    <h3>Buscar anuncios</h3>
                    <form method="get" action="/empleos">
                        <input type="text" name="q" placeholder="Cargo, empresa o palabra clave">
                        <select name="departamento_id" id="home_departamento_id">
                            <option value="">Departamento</option>
                            <?php if (!empty($departamentos) && (is_array($departamentos) || $departamentos instanceof Traversable)): ?>
                                <?php foreach ($departamentos as $dep): ?>
                                    <option value="<?php echo (int)$dep['id']; ?>"><?php echo htmlspecialchars($dep['nombre']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <select name="provincia_id" id="home_provincia_id" disabled>
                            <option value="">Provincia</option>
                        </select>
                        <select name="distrito_id" id="home_distrito_id" disabled>
                            <option value="">Distrito</option>
                        </select>
                        <select name="tipo">
                            <option value="">Tipo de empleo</option>
                            <option value="tiempo_completo">Tiempo completo</option>
                            <option value="medio_tiempo">Medio tiempo</option>
                            <option value="practicas">Practicas</option>
                        </select>
                        <select name="modalidad">
                            <option value="">Modalidad</option>
                            <option value="presencial">Presencial</option>
                            <option value="remoto">Remoto</option>
                            <option value="hibrido">Hibrido</option>
                        </select>
                        <button type="submit" class="btn-primary">Buscar anuncios</button>
                    </form>
                    <div class="quick-links">
                        <a href="/publicar-empleo">Publicar gratis</a>
                        <a href="/empleos?order=mas_vistos">Mas vistos</a>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    

    <section class="home-sponsor-banner" aria-label="Publicidad en inicio">
        <?php
            $heroLink = $featuredPublicidad['enlace'] ?? 'https://www.computrabajo.com';
            $heroTitle = trim((string)($featuredPublicidad['titulo'] ?? ''));
            $heroDesc = trim((string)($featuredPublicidad['descripcion'] ?? ''));
            $heroImage = $featuredPublicidad['imagen'] ?? '';
            $heroStyle = '';
            if (!empty($heroImage)) {
                $heroStyle = " style=\"--ad-bg:url('" . htmlspecialchars(BASE_PATH . '/' . ltrim($heroImage, '/')) . "');\"";
            }
        ?>

        <?php if ($isAdmin): ?>
            <a class="home-sponsor-add-btn" href="/admin-publicidad" title="Agregar publicidad" aria-label="Agregar publicidad">+</a>
        <?php endif; ?>

        <a class="home-sponsor-hero" href="<?php echo htmlspecialchars($heroLink); ?>" target="_blank" rel="noopener noreferrer"<?php echo $heroStyle; ?>>
            <div class="home-sponsor-copy">
                <?php if ($heroTitle !== ''): ?>
                    <h3><?php echo htmlspecialchars($heroTitle); ?></h3>
                <?php endif; ?>
                <?php if ($heroDesc !== ''): ?>
                    <p><?php echo htmlspecialchars($heroDesc); ?></p>
                <?php endif; ?>
            </div>
        </a>
    </section>

    <?php if (!empty($olderPublicidades)): ?>
    <section class="home-sponsor-older" id="publicidades-anteriores" aria-label="Publicidades anteriores">
        <?php foreach ($olderPublicidades as $pub): ?>
            <?php
                $itemImg = $pub['imagen'] ?? '';
                $itemStyle = '';
                if (!empty($itemImg)) {
                    $itemStyle = " style=\"--ad-bg:url('" . htmlspecialchars(BASE_PATH . '/' . ltrim($itemImg, '/')) . "');\"";
                }
            ?>
            <article class="home-sponsor-older-card">
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
            </article>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>

    <?php if (!$isAdmin && !empty($olderPublicidades)): ?>
    <div class="home-sponsor-more-wrap">
        <a class="btn-outline home-sponsor-more-btn" href="/publicidades">Ver mas publicidad</a>
    </div>
    <?php endif; ?>

    <section class="categories categories-collapsible is-mobile-collapsed" id="home-categories">
        <?php
            $subcategoriasByParent = [];
            if (!empty($categorias) && isset($conexion)) {
                $categoriaIds = [];
                foreach ($categorias as $catItem) {
                    $cid = (int)($catItem['id'] ?? 0);
                    if ($cid > 0) {
                        $categoriaIds[] = $cid;
                    }
                }

                if (!empty($categoriaIds)) {
                    $categoriaIds = array_values(array_unique($categoriaIds));
                    $placeholders = implode(',', array_fill(0, count($categoriaIds), '?'));
                    $sqlSubs = "SELECT id, nombre, parent_id, fecha_creacion
                                FROM categorias
                                WHERE estado = 'activo'
                                  AND parent_id IN ($placeholders)
                                ORDER BY parent_id ASC, fecha_creacion DESC, id DESC";
                    $stmtSubs = $conexion->prepare($sqlSubs);
                    $stmtSubs->execute($categoriaIds);
                    $rowsSubs = $stmtSubs->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($rowsSubs as $sub) {
                        $pid = (int)($sub['parent_id'] ?? 0);
                        if ($pid <= 0) {
                            continue;
                        }
                        if (!isset($subcategoriasByParent[$pid])) {
                            $subcategoriasByParent[$pid] = [];
                        }
                        $subcategoriasByParent[$pid][] = $sub;
                    }
                }
            }
        ?>
        <div class="section-title">
            <div>
                <span class="section-kicker">Categoria empleos</span>
                <h3>Explorar por categoria</h3>
            </div>
            <div class="section-actions">
                <?php if ($isAdmin): ?>
                    <a class="btn-outline" href="/admin-categorias">Gestion de categorias</a>
                <?php endif; ?>
                <button type="button" class="btn-outline categories-toggle-btn" id="toggle-categories" aria-controls="home-categories-grid" aria-expanded="false">Mostrar categorias</button>
            </div>
        </div>
        <div class="category-grid directory-grid" id="home-categories-grid">
            <?php if (!empty($categorias) && (is_array($categorias) || $categorias instanceof Traversable)): ?>
                <?php foreach ($categorias as $categoria): ?>
                    <?php $nombreCategoria = $categoria['nombre'] ?? 'Categoria'; ?>
                    <?php $subItems = $subcategoriasByParent[(int)($categoria['id'] ?? 0)] ?? []; ?>
                    <article class="category-card">
                        <a class="category-main-link" href="/empleos?q=<?php echo urlencode($nombreCategoria); ?>">
                            <strong><?php echo htmlspecialchars($nombreCategoria); ?></strong>
                        </a>
                        <a class="category-view-link" href="/empleos?q=<?php echo urlencode($nombreCategoria); ?>">Ver anuncios</a>
                        <?php if (!empty($subItems)): ?>
                            <select class="category-subcat-select" aria-label="Subcategorias de <?php echo htmlspecialchars($nombreCategoria); ?>" onchange="if (this.value) { window.location.href = this.value; }">
                                <option value="">Mostrar mas</option>
                                <?php foreach ($subItems as $sub): ?>
                                    <?php $subNombre = (string)($sub['nombre'] ?? ''); ?>
                                    <option value="/empleos?q=<?php echo urlencode($subNombre); ?>"><?php echo htmlspecialchars($subNombre); ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <a class="category-card" href="/empleos?q=Contabilidad"><strong>Contabilidad</strong><span>Ver anuncios</span></a>
                <a class="category-card" href="/empleos?q=Administracion"><strong>Administracion</strong><span>Ver anuncios</span></a>
                <a class="category-card" href="/empleos?q=Informatica"><strong>Informatica</strong><span>Ver anuncios</span></a>
                <a class="category-card" href="/empleos?q=Marketing"><strong>Marketing</strong><span>Ver anuncios</span></a>
                <a class="category-card" href="/empleos?q=Ventas"><strong>Ventas</strong><span>Ver anuncios</span></a>
                <a class="category-card" href="/empleos?q=Recursos Humanos"><strong>Recursos Humanos</strong><span>Ver anuncios</span></a>
            <?php endif; ?>
        </div>
    </section>

    <section class="market-layout">
        <div class="market-main latest-jobs">
            <div class="section-title">
                <div>
                    <span class="section-kicker">Ofertas de trabajo</span>
                    <h3>Ultimos empleos publicados</h3>
                </div>
            </div>
            <div class="jobs-grid portal-jobs-grid">
                <?php
                if (!empty($recientes) && (is_array($recientes) || $recientes instanceof Traversable)) {
                    $preparedJobs = [];
                    foreach ($recientes as $job) {
                        $title = $job['titulo'] ?? 'Sin titulo';
                        $desc = $job['descripcion'] ?? '';
                        $slug = $job['slug'] ?? '';
                        $url = !empty($slug) ? '/empleo/'.urlencode($slug) : '#';
                        $empresa = $job['empresa'] ?? 'Empresa';
                        $fecha = !empty($job['fecha_publicacion']) ? date('d/m/Y', strtotime($job['fecha_publicacion'])) : '';
                        $modalidad = $job['modalidad'] ?? 'Presencial';
                        $salario = $job['salario'] ?? 'A convenir';
                        $tags = !empty($job['tags']) ? array_slice(array_filter(array_map('trim', explode(',', $job['tags']))), 0, 3) : [];

                        $imagenName = $job['imagen'] ?? '';
                        $thumbPath = '';
                        if (!empty($imagenName)) {
                            $thumbFile = __DIR__ . '/../../uploads/empleos/thumb_' . basename($imagenName);
                            $origFile = __DIR__ . '/../../uploads/empleos/' . basename($imagenName);
                            if (is_file($thumbFile)) {
                                $thumbPath = BASE_PATH . '/uploads/empleos/thumb_' . basename($imagenName);
                            } elseif (is_file($origFile)) {
                                $thumbPath = BASE_PATH . '/uploads/empleos/' . basename($imagenName);
                            }
                        }

                        $isFeatured = !empty($job['destacado']) && (int)$job['destacado'] === 1;

                        $preparedJobs[] = [
                            'title' => $title,
                            'desc' => $desc,
                            'url' => $url,
                            'empresa' => $empresa,
                            'fecha' => $fecha,
                            'modalidad' => $modalidad,
                            'salario' => $salario,
                            'tags' => $tags,
                            'thumbPath' => $thumbPath,
                            'isFeatured' => $isFeatured,
                            'isHighlighted' => $isFeatured && !empty($thumbPath),
                        ];
                    }

                    $featuredJobs = [];
                    $regularJobs = [];
                    foreach ($preparedJobs as $jobItem) {
                        if (!empty($jobItem['isFeatured'])) {
                            $featuredJobs[] = $jobItem;
                        } else {
                            $regularJobs[] = $jobItem;
                        }
                    }

                    // Orden fijo en portada: destacados arriba, luego no destacados.
                    $preparedJobs = array_merge($featuredJobs, $regularJobs);

                    foreach ($preparedJobs as $item) {
                        $cardClass = 'job-card portal-job-card' . ($item['isHighlighted'] ? ' is-highlighted' : '');
                        echo '<article class="'.htmlspecialchars($cardClass).'">';
                        if (!empty($item['thumbPath'])) {
                            echo '<div class="job-thumb"><img src="'.htmlspecialchars($item['thumbPath']).'" alt="'.htmlspecialchars($item['title']).'" /></div>';
                        }
                        echo '<div class="job-body">';
                        echo '<div class="job-meta"><span>'.htmlspecialchars($item['empresa']).'</span><span>'.htmlspecialchars($item['fecha']).'</span></div>';
                        echo '<div class="job-title"><a href="'.htmlspecialchars($item['url']).'">'.htmlspecialchars($item['title']).'</a></div>';
                        if (!empty($item['desc'])) echo '<div class="job-desc">'.htmlspecialchars(mb_strimwidth($item['desc'],0,180,'...')).'</div>';
                        echo '<div class="job-tags">';
                        echo '<span class="tag is-accent">'.htmlspecialchars($item['modalidad']).'</span>';
                        foreach ($item['tags'] as $tag) {
                            echo '<span class="tag">'.htmlspecialchars($tag).'</span>';
                        }
                        echo '</div>';
                        echo '<div class="job-actions"><div><span class="salary">'.htmlspecialchars($item['salario']).'</span></div><a class="btn-outline" href="'.htmlspecialchars($item['url']).'">Ver anuncio</a></div>';
                        echo '</div>';
                        echo '</article>';
                    }
                } else {
                    for ($i=1;$i<=6;$i++){
                        echo '<article class="job-card portal-job-card">';
                        echo '<div class="job-body">';
                        echo '<div class="job-meta"><span>Empresa</span><span>Hace '.$i.' dias</span></div>';
                        echo '<div class="job-title"><a href="#">Oferta '.$i.'</a></div>';
                        echo '<div class="job-desc">Descripcion corta de la oferta '.$i.'.</div>';
                        echo '<div class="job-actions"><span class="salary">A convenir</span><a class="btn-outline" href="#">Ver anuncio</a></div>';
                        echo '</div>';
                        echo '</article>';
                    }
                }
                ?>
            </div>
            <div class="latest-jobs-more-wrap">
                <a href="/empleos" class="latest-jobs-more-link">Ver mas anuncios</a>
            </div>
        </div>

        <aside class="market-sidebar">
            <div class="sidebar-card emphasis-card">
                <span class="section-kicker">Publicar anuncio</span>
                <h3>Haz visible tu vacante hoy</h3>
                <p>Destaca tu aviso en portada, agrega datos de contacto y recibe postulaciones directas desde la ficha.</p>
                <a class="btn-primary" href="/publicar-empleo">Crear anuncio</a>
            </div>
            <div class="sidebar-card info-card">
                <h4>Que puedes encontrar</h4>
                <ul>
                    <li>Empleos por categoria</li>
                    <li>Vacantes recientes y destacadas</li>
                    <li>Anuncios con salario y modalidad</li>
                    <li>Contacto directo con la empresa</li>
                </ul>
            </div>
        </aside>
    </section>

</div>

<script src="public/assets/js/carousel.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var departamentoSel = document.getElementById('home_departamento_id');
    var provinciaSel = document.getElementById('home_provincia_id');
    var distritoSel = document.getElementById('home_distrito_id');

    function resetSelect(select, placeholder) {
        select.innerHTML = '<option value="">' + placeholder + '</option>';
        select.disabled = true;
    }

    function fillSelect(select, placeholder, rows) {
        resetSelect(select, placeholder);
        if (!Array.isArray(rows) || rows.length === 0) return;
        rows.forEach(function (row) {
            var opt = document.createElement('option');
            opt.value = row.id;
            opt.textContent = row.nombre;
            select.appendChild(opt);
        });
        select.disabled = false;
    }

    if (departamentoSel && provinciaSel && distritoSel) {
        departamentoSel.addEventListener('change', function () {
            resetSelect(provinciaSel, 'Provincia');
            resetSelect(distritoSel, 'Distrito');
            if (!this.value) return;

            fetch('/provincias?departamento_id=' + encodeURIComponent(this.value))
                .then(function (res) { return res.json(); })
                .then(function (data) { fillSelect(provinciaSel, 'Provincia', data); })
                .catch(function () { resetSelect(provinciaSel, 'Provincia'); });
        });

        provinciaSel.addEventListener('change', function () {
            resetSelect(distritoSel, 'Distrito');
            if (!this.value) return;

            fetch('/distritos?provincia_id=' + encodeURIComponent(this.value))
                .then(function (res) { return res.json(); })
                .then(function (data) { fillSelect(distritoSel, 'Distrito', data); })
                .catch(function () { resetSelect(distritoSel, 'Distrito'); });
        });
    }

    var categoriesSection = document.getElementById('home-categories');
    var categoriesToggle = document.getElementById('toggle-categories');

    if (categoriesSection && categoriesToggle) {
        categoriesToggle.addEventListener('click', function () {
            var isCollapsed = categoriesSection.classList.toggle('is-mobile-collapsed');
            categoriesToggle.textContent = isCollapsed ? 'Mostrar categorias' : 'Ver menos';
            categoriesToggle.setAttribute('aria-expanded', isCollapsed ? 'false' : 'true');
        });
    }
});
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>