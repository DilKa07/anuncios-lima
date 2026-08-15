<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
  <?php
    $resultados = $resultados ?? [];
    $locationSuggestions = $locationSuggestions ?? [];
    $departamentos = $departamentos ?? [];
    $provincias = $provincias ?? [];
    $distritos = $distritos ?? [];
    $selectedLugar = trim($_GET['lugar'] ?? '');
    $selectedDepartamentoId = isset($_GET['departamento_id']) ? (int)$_GET['departamento_id'] : 0;
    $selectedProvinciaId = isset($_GET['provincia_id']) ? (int)$_GET['provincia_id'] : 0;
    $selectedDistritoId = isset($_GET['distrito_id']) ? (int)$_GET['distrito_id'] : 0;
    $selectedSlug = trim($_GET['sel'] ?? '');
    $selectedJob = $resultados[0] ?? null;
    $safeSubstr = static function ($value, $start, $length) {
      $value = (string)$value;
      if (function_exists('mb_substr')) {
        return mb_substr($value, $start, $length);
      }
      return substr($value, $start, $length);
    };
    foreach ($resultados as $candidate) {
      if (!empty($selectedSlug) && ($candidate['slug'] ?? '') === $selectedSlug) {
        $selectedJob = $candidate;
        break;
      }
    }

    $filterParams = $_GET;
    unset($filterParams['sel']);
    unset($filterParams['url']);
  ?>

  <div class="ctj-top-controls" id="ctjTopControls">
    <section class="ctj-search-wrap">
      <div class="ctj-search-row">
        <form method="get" action="/empleos" class="ctj-search-form">
          <div class="ctj-search-field">
            <span class="ctj-icon">&#128188;</span>
            <input type="text" name="q" placeholder="Puesto, palabra clave o empresa" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
          </div>
          <div class="ctj-search-field">
            <span class="ctj-icon">&#128205;</span>
            <select name="lugar" aria-label="Distrito, provincia o departamento">
              <option value="">Distrito, provincia o departamento</option>
              <?php foreach ($locationSuggestions as $locationOption): ?>
                <option value="<?php echo htmlspecialchars($locationOption); ?>" <?php echo ($selectedLugar === (string)$locationOption) ? 'selected' : ''; ?>><?php echo htmlspecialchars($locationOption); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button class="btn-primary ctj-search-btn" type="submit" aria-label="Buscar">&#128269;</button>
          <button type="button" class="ctj-mobile-menu-btn" id="ctjMobileMenuBtn" aria-controls="ctjToolbar" aria-expanded="false">MENU</button>
        </form>
      </div>
    </section>

    <form method="get" action="/empleos" class="ctj-toolbar" id="ctjToolbar">
      <input type="hidden" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
      <input type="hidden" name="lugar" value="<?php echo htmlspecialchars($selectedLugar); ?>">
      <div class="ctj-toolbar-left">Filtrar resultados</div>
      <div class="ctj-toolbar-right">
        <select name="order" class="filter-chip ctj-chip-fit" onchange="this.form.submit()">
          <option value="recientes" <?php if(($_GET['order'] ?? '')=='recientes' || empty($_GET['order'])) echo 'selected'; ?>>Fecha</option>
          <option value="mas_vistos" <?php if(($_GET['order'] ?? '')=='mas_vistos') echo 'selected'; ?>>Mas vistos</option>
        </select>
        <select name="departamento_id" class="filter-chip ctj-chip-fit" onchange="this.form.submit()">
          <option value="">Departamento</option>
          <?php foreach ($departamentos as $dep): ?>
            <option value="<?php echo (int)($dep['id'] ?? 0); ?>" <?php echo ($selectedDepartamentoId === (int)($dep['id'] ?? 0)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dep['nombre'] ?? 'Departamento'); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="provincia_id" class="filter-chip ctj-chip-fit" onchange="this.form.submit()">
          <option value="">Provincia</option>
          <?php foreach ($provincias as $prov): ?>
            <option value="<?php echo (int)($prov['id'] ?? 0); ?>" <?php echo ($selectedProvinciaId === (int)($prov['id'] ?? 0)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($prov['nombre'] ?? 'Provincia'); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="distrito_id" class="filter-chip ctj-chip-fit" onchange="this.form.submit()">
          <option value="">Distrito</option>
          <?php foreach ($distritos as $dist): ?>
            <option value="<?php echo (int)($dist['id'] ?? 0); ?>" <?php echo ($selectedDistritoId === (int)($dist['id'] ?? 0)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dist['nombre'] ?? 'Distrito'); ?></option>
          <?php endforeach; ?>
        </select>
        <a href="/publicar-empleo" class="btn-outline ctj-post-link">Publicar empleo</a>
        <a href="/" class="btn-outline ctj-post-link">Inicio</a>
      </div>
    </form>
  </div>
  <div class="ctj-top-controls-spacer" id="ctjTopControlsSpacer" aria-hidden="true"></div>
  <div class="ctj-mobile-overlay" id="ctjMobileOverlay" aria-hidden="true"></div>

  <?php if (empty($resultados)): ?>
    <div class="empty-state">
      <h3>No se encontraron empleos</h3>
      <p>Ajusta los filtros o publica un nuevo anuncio para empezar a mover la categoría.</p>
    </div>
  <?php else: ?>

    <section class="ctj-layout ctj-layout-board">
      <main class="ctj-list-pane">
        <div class="ctj-result-head">
          <p><strong><?php echo (int)($total ?? 0); ?></strong> Ofertas encontradas</p>
          <button type="button" class="ctj-alert-btn">Crear alerta</button>
        </div>

        <div class="ctj-list">
          <?php foreach ($resultados as $job): ?>
            <?php
              $jobSlug = $job['slug'] ?? '';
              $isActive = $selectedJob && ($selectedJob['slug'] ?? '') === $jobSlug;

              $thumb = '';
              if (!empty($job['imagen'])) {
                  $imgBase = basename($job['imagen']);
                  $thumbFile = __DIR__ . '/../../uploads/empleos/thumb_' . $imgBase;
                  $origFile = __DIR__ . '/../../uploads/empleos/' . $imgBase;
                  if (is_file($thumbFile)) $thumb = BASE_PATH . '/uploads/empleos/thumb_' . $imgBase;
                  elseif (is_file($origFile)) $thumb = BASE_PATH . '/uploads/empleos/' . $imgBase;
              }
              $isFeaturedWithImage = !empty($thumb) && !empty($job['destacado']) && (int)$job['destacado'] === 1;
              $jobTitle = $job['titulo'] ?? 'Sin titulo';
              $jobCompany = $job['empresa'] ?? 'Empresa';
              $jobLocationParts = array_filter([
                $job['distrito'] ?? '',
                $job['provincia'] ?? '',
                $job['departamento'] ?? '',
              ]);
              $jobLocation = !empty($jobLocationParts) ? implode(', ', $jobLocationParts) : ($job['modalidad'] ?? 'No especificada');
              $jobDescription = $job['descripcion'] ?? 'Sin descripcion disponible.';
              $jobTipo = $job['tipo_trabajo'] ?? 'No especificado';
              $jobModalidad = $job['modalidad'] ?? 'No especificada';
              $jobSalario = $job['salario'] ?? 'A convenir';
              $jobFallback = $safeSubstr($jobCompany ?: $jobTitle, 0, 1);
              $jobTimeLabel = !empty($job['fecha_publicacion']) ? 'Publicado el ' . date('d/m/Y', strtotime($job['fecha_publicacion'])) : 'Fecha no disponible';

              $itemUrl = '/empleo/' . rawurlencode($jobSlug);
              $listQuery = $filterParams;
              $listQuery['sel'] = $jobSlug;
              $selectUrl = '/empleos?' . http_build_query($listQuery);
            ?>

            <a
              class="ctj-card-link"
              href="<?php echo htmlspecialchars($selectUrl); ?>"
              data-slug="<?php echo htmlspecialchars($jobSlug, ENT_QUOTES, 'UTF-8'); ?>"
              data-url="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>"
              data-title="<?php echo htmlspecialchars($jobTitle, ENT_QUOTES, 'UTF-8'); ?>"
              data-company="<?php echo htmlspecialchars($jobCompany, ENT_QUOTES, 'UTF-8'); ?>"
              data-location="<?php echo htmlspecialchars($jobLocation, ENT_QUOTES, 'UTF-8'); ?>"
              data-description="<?php echo htmlspecialchars($jobDescription, ENT_QUOTES, 'UTF-8'); ?>"
              data-tipo="<?php echo htmlspecialchars($jobTipo, ENT_QUOTES, 'UTF-8'); ?>"
              data-modalidad="<?php echo htmlspecialchars($jobModalidad, ENT_QUOTES, 'UTF-8'); ?>"
              data-salario="<?php echo htmlspecialchars($jobSalario, ENT_QUOTES, 'UTF-8'); ?>"
              data-thumb="<?php echo htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8'); ?>"
              data-fallback="<?php echo htmlspecialchars($jobFallback, ENT_QUOTES, 'UTF-8'); ?>"
            >
              <article class="ctj-card<?php echo $isActive ? ' is-active' : ''; ?><?php echo $isFeaturedWithImage ? ' is-featured' : ''; ?>">
                <div class="ctj-card-head">
                  <div class="ctj-head-left">
                    <?php if ($isFeaturedWithImage): ?>
                      <span class="ctj-tag-danger">Urgente</span>
                      <span class="ctj-badge">Empleo destacado</span>
                    <?php else: ?>
                      <span class="ctj-badge">Oferta activa</span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($job['fecha_publicacion'])): ?>
                    <span class="ctj-date"><?php echo date('d/m/Y', strtotime($job['fecha_publicacion'])); ?></span>
                  <?php endif; ?>
                </div>

                <div class="ctj-card-body">
                  <?php if (!empty($thumb)): ?>
                    <img class="ctj-logo" src="<?php echo htmlspecialchars($thumb); ?>" alt="<?php echo htmlspecialchars($job['titulo'] ?? 'Oferta'); ?>">
                  <?php else: ?>
                    <div class="ctj-logo ctj-logo-fallback"><?php echo htmlspecialchars($safeSubstr($job['empresa'] ?? ($job['titulo'] ?? 'E'), 0, 1)); ?></div>
                  <?php endif; ?>

                  <div class="ctj-main">
                    <h3><?php echo htmlspecialchars($jobTitle); ?></h3>
                    <p class="ctj-company"><?php echo htmlspecialchars($jobCompany); ?></p>
                    <p class="ctj-location"><?php echo htmlspecialchars($jobLocation); ?></p>
                    <p class="ctj-time"><?php echo htmlspecialchars($jobTimeLabel); ?></p>
                  </div>

                  <div class="ctj-side">
                    <p class="ctj-salary"><?php echo htmlspecialchars($job['salario'] ?? 'A convenir'); ?></p>
                    <span class="ctj-inline-link">Vista</span>
                  </div>
                </div>
              </article>
            </a>
          <?php endforeach; ?>
        </div>
      </main>

      <aside class="ctj-detail-pane" id="ctjDetailPane">
        <?php if (!empty($selectedJob)): ?>
          <?php
            $selectedSlugSafe = $selectedJob['slug'] ?? '';
            $selectedUrl = '/empleo/' . rawurlencode($selectedSlugSafe);
            $selectedThumb = '';
            if (!empty($selectedJob['imagen'])) {
                $imgBase = basename($selectedJob['imagen']);
                $thumbFile = __DIR__ . '/../../uploads/empleos/thumb_' . $imgBase;
                $origFile = __DIR__ . '/../../uploads/empleos/' . $imgBase;
                if (is_file($thumbFile)) $selectedThumb = BASE_PATH . '/uploads/empleos/thumb_' . $imgBase;
                elseif (is_file($origFile)) $selectedThumb = BASE_PATH . '/uploads/empleos/' . $imgBase;
            }
          ?>

          <article class="ctj-detail-card">
            <div class="ctj-detail-head">
              <div>
                <h3><?php echo htmlspecialchars($selectedJob['titulo'] ?? 'Sin titulo'); ?></h3>
                <p class="ctj-detail-company"><?php echo htmlspecialchars($selectedJob['empresa'] ?? 'Empresa'); ?></p>
                <?php
                  $selectedLocationParts = array_filter([
                    $selectedJob['distrito'] ?? '',
                    $selectedJob['provincia'] ?? '',
                    $selectedJob['departamento'] ?? '',
                  ]);
                  $selectedLocation = !empty($selectedLocationParts) ? implode(', ', $selectedLocationParts) : ($selectedJob['modalidad'] ?? 'No especificada');
                ?>
                <p class="ctj-detail-location"><?php echo htmlspecialchars($selectedLocation); ?></p>
              </div>

              <?php if (!empty($selectedThumb)): ?>
                <img src="<?php echo htmlspecialchars($selectedThumb); ?>" alt="<?php echo htmlspecialchars($selectedJob['titulo'] ?? 'Oferta'); ?>">
              <?php else: ?>
                <div class="ctj-detail-fallback"><?php echo htmlspecialchars($safeSubstr($selectedJob['empresa'] ?? ($selectedJob['titulo'] ?? 'E'), 0, 1)); ?></div>
              <?php endif; ?>
            </div>

            <div class="ctj-detail-scroll">
              <div class="ctj-detail-actions">
                <a class="btn-primary" href="<?php echo htmlspecialchars($selectedUrl); ?>">Postularme</a>
                <button type="button" class="ctj-icon-btn" aria-label="Guardar">&#9825;</button>
                <button type="button" class="ctj-icon-btn" aria-label="Compartir">&#10150;</button>
                <button type="button" class="ctj-icon-btn" aria-label="Mas opciones">&#8942;</button>
              </div>

              <ul class="ctj-detail-meta">
                <li><strong>Horario de trabajo:</strong> <?php echo htmlspecialchars($selectedJob['tipo_trabajo'] ?? 'No especificado'); ?></li>
                <li><strong>Modalidad:</strong> <?php echo htmlspecialchars($selectedJob['modalidad'] ?? 'No especificada'); ?></li>
                <li><strong>Salario:</strong> <?php echo htmlspecialchars($selectedJob['salario'] ?? 'A convenir'); ?></li>
              </ul>

              <div class="ctj-detail-copy">
                <?php echo nl2br(htmlspecialchars($selectedJob['descripcion'] ?? 'Sin descripcion disponible.')); ?>
              </div>
            </div>
          </article>
        <?php endif; ?>
      </aside>
    </section>

    <?php if (!empty($paginacion_html)): ?>
      <div class="pagination-wrap"><?php echo $paginacion_html; ?></div>
    <?php endif; ?>
  <?php endif; ?>

</div>

<script>
  (function () {
    document.body.classList.add('empleos-no-header');

    var topControls = document.getElementById('ctjTopControls');
    var topSpacer = document.getElementById('ctjTopControlsSpacer');
    var toolbar = document.getElementById('ctjToolbar');
    var mobileMenuBtn = document.getElementById('ctjMobileMenuBtn');
    var mobileOverlay = document.getElementById('ctjMobileOverlay');
    var fitChips = Array.prototype.slice.call(document.querySelectorAll('.ctj-chip-fit'));
    var extraTopGap = 14;
    var mobileMedia = window.matchMedia('(max-width: 720px)');

    function syncTopControlsSpacer() {
      if (!topControls || !topSpacer) return;
      topSpacer.style.height = (topControls.offsetHeight + extraTopGap) + 'px';
    }

    syncTopControlsSpacer();
    window.addEventListener('resize', syncTopControlsSpacer);

    function syncFilterChipWidths() {
      if (!fitChips.length) return;
      if (mobileMedia.matches) {
        fitChips.forEach(function (chip) {
          chip.style.width = '';
        });
        return;
      }

      fitChips.forEach(function (chip) {
        var selectedText = '';
        if (chip.options && chip.selectedIndex >= 0) {
          selectedText = (chip.options[chip.selectedIndex].text || '').trim();
        }
        if (!selectedText) {
          selectedText = (chip.getAttribute('aria-label') || chip.name || 'Filtro').trim();
        }

        var minChars = 9;
        var maxChars = 28;
        var chars = Math.max(minChars, Math.min(maxChars, selectedText.length + 4));
        chip.style.width = chars + 'ch';
      });
    }

    fitChips.forEach(function (chip) {
      chip.addEventListener('change', syncFilterChipWidths);
    });

    syncFilterChipWidths();

    function setToolbarState(open) {
      if (!toolbar || !mobileMenuBtn) return;
      toolbar.classList.toggle('is-open', !!open);
      mobileMenuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function syncToolbarStateByViewport() {
      if (!mobileMedia.matches) {
        if (toolbar) toolbar.classList.remove('is-open');
        if (mobileMenuBtn) mobileMenuBtn.setAttribute('aria-expanded', 'false');
        syncFilterChipWidths();
        return;
      }
      if (toolbar) toolbar.classList.remove('is-open');
      if (mobileMenuBtn) mobileMenuBtn.setAttribute('aria-expanded', 'false');
      syncFilterChipWidths();
    }

    if (mobileMenuBtn && toolbar) {
      mobileMenuBtn.addEventListener('click', function () {
        var willOpen = !toolbar.classList.contains('is-open');
        setToolbarState(willOpen);
      });
    }

    if (typeof mobileMedia.addEventListener === 'function') {
      mobileMedia.addEventListener('change', syncToolbarStateByViewport);
    } else if (typeof mobileMedia.addListener === 'function') {
      mobileMedia.addListener(syncToolbarStateByViewport);
    }

    syncToolbarStateByViewport();

    var links = Array.prototype.slice.call(document.querySelectorAll('.ctj-card-link'));
    var detailPane = document.getElementById('ctjDetailPane');
    if (!links.length || !detailPane) return;

    function closeMobileDetail() {
      detailPane.classList.remove('is-mobile-open');
      if (mobileOverlay) mobileOverlay.classList.remove('is-open');
      document.body.classList.remove('ctj-mobile-detail-open');
    }

    function openMobileDetail() {
      detailPane.classList.add('is-mobile-open');
      if (mobileOverlay) mobileOverlay.classList.add('is-open');
      document.body.classList.add('ctj-mobile-detail-open');
    }

    if (mobileOverlay) {
      mobileOverlay.addEventListener('click', closeMobileDetail);
    }

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        closeMobileDetail();
      }
    });

    function esc(text) {
      return String(text || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function toHtmlWithBreaks(text) {
      return esc(text).replace(/\n/g, '<br>');
    }

    function renderDetail(link) {
      var d = link.dataset;
      var media = d.thumb
        ? '<img src="' + esc(d.thumb) + '" alt="' + esc(d.title) + '">'
        : '<div class="ctj-detail-fallback">' + esc(d.fallback || 'E') + '</div>';

      detailPane.innerHTML = '' +
        '<article class="ctj-detail-card">' +
          '<button type="button" class="ctj-detail-close" aria-label="Cerrar detalle">&times;</button>' +
          '<div class="ctj-detail-head">' +
            '<div>' +
              '<h3>' + esc(d.title) + '</h3>' +
              '<p class="ctj-detail-company">' + esc(d.company) + '</p>' +
              '<p class="ctj-detail-location">' + esc(d.location) + '</p>' +
            '</div>' +
            media +
          '</div>' +
          '<div class="ctj-detail-scroll">' +
            '<div class="ctj-detail-actions">' +
              '<a class="btn-primary" href="' + esc(d.url) + '">Postularme</a>' +
              '<button type="button" class="ctj-icon-btn" aria-label="Guardar">&#9825;</button>' +
              '<button type="button" class="ctj-icon-btn" aria-label="Compartir">&#10150;</button>' +
              '<button type="button" class="ctj-icon-btn" aria-label="Mas opciones">&#8942;</button>' +
            '</div>' +
            '<ul class="ctj-detail-meta">' +
              '<li><strong>Horario de trabajo:</strong> ' + esc(d.tipo || 'No especificado') + '</li>' +
              '<li><strong>Modalidad:</strong> ' + esc(d.modalidad || 'No especificada') + '</li>' +
              '<li><strong>Salario:</strong> ' + esc(d.salario || 'A convenir') + '</li>' +
            '</ul>' +
            '<div class="ctj-detail-copy">' + toHtmlWithBreaks(d.description || 'Sin descripcion disponible.') + '</div>' +
          '</div>' +
        '</article>';

      var closeBtn = detailPane.querySelector('.ctj-detail-close');
      if (closeBtn) {
        closeBtn.addEventListener('click', function () {
          closeMobileDetail();
        });
      }
    }

    function setActive(link) {
      links.forEach(function (a) {
        var card = a.querySelector('.ctj-card');
        if (card) card.classList.remove('is-active');
      });
      var activeCard = link.querySelector('.ctj-card');
      if (activeCard) activeCard.classList.add('is-active');
    }

    links.forEach(function (link) {
      link.addEventListener('click', function (event) {
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        setActive(link);
        renderDetail(link);
        if (mobileMedia.matches) {
          openMobileDetail();
        }
        history.pushState({ sel: link.dataset.slug || '' }, '', link.getAttribute('href'));
      });
    });

    window.addEventListener('popstate', function () {
      var current = new URL(window.location.href);
      var sel = current.searchParams.get('sel');
      var target = null;

      if (sel) {
        target = links.find(function (a) { return (a.dataset.slug || '') === sel; }) || null;
      }
      if (!target && links.length) target = links[0];

      if (target) {
        setActive(target);
        renderDetail(target);
        if (mobileMedia.matches) {
          openMobileDetail();
        }
      }
    });
  })();
</script>

<?php require_once 'app/views/layouts/footer.php'; ?>
