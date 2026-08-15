<?php require_once 'app/views/layouts/header.php'; ?>

<div class="container">
  <?php if (empty($empleo)): ?>
    <div class="empty-state">
      <h3>Empleo no encontrado</h3>
      <p>El anuncio solicitado ya no esta disponible o fue retirado del portal.</p>
      <a class="btn-outline" href="/empleos" style="margin-top:14px;display:inline-flex">Ver todos los empleos</a>
    </div>
  <?php else: ?>

    <!-- breadcrumb -->
    <nav class="detail-breadcrumb">
      <a href="/">Inicio</a>
      <span>›</span>
      <a href="/empleos">Empleos</a>
      <span>›</span>
      <span><?php echo htmlspecialchars($empleo['titulo']); ?></span>
    </nav>

    <!-- portada -->
    <?php
      $imgSrc = '';
      if (!empty($empleo['imagen'])) {
          $base = basename($empleo['imagen']);
          $thumbFile = __DIR__ . '/../../uploads/empleos/thumb_' . $base;
          $origFile  = __DIR__ . '/../../uploads/empleos/' . $base;
          if (is_file($origFile))  $imgSrc = BASE_PATH . '/uploads/empleos/' . $base;
          elseif (is_file($thumbFile)) $imgSrc = BASE_PATH . '/uploads/empleos/thumb_' . $base;
      }
    ?>
    <?php if ($imgSrc): ?>
    <div class="detail-portada">
      <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($empleo['titulo']); ?>">
    </div>
    <?php endif; ?>

    <!-- layout principal -->
    <div class="detail-layout">

      <!-- columna izquierda -->
      <div class="detail-main">

        <!-- hero del anuncio -->
        <div class="detail-head">
          <div class="detail-head-top">
            <span class="section-kicker">Anuncio laboral</span>
            <?php if (!empty($empleo['destacado']) && $empleo['destacado'] == 1): ?>
              <span class="carousel-badge" style="position:static;display:inline-flex">Destacado</span>
            <?php endif; ?>
          </div>
          <h1 class="detail-title"><?php echo htmlspecialchars($empleo['titulo']); ?></h1>
          <div class="detail-badges">
            <span class="badge-empresa"><?php echo htmlspecialchars($empleo['empresa'] ?? 'Empresa'); ?></span>
            <?php if (!empty($empleo['modalidad'])): ?>
              <span class="tag is-accent"><?php echo htmlspecialchars($empleo['modalidad']); ?></span>
            <?php endif; ?>
            <?php if (!empty($empleo['tipo_trabajo'])): ?>
              <span class="tag"><?php echo htmlspecialchars($empleo['tipo_trabajo']); ?></span>
            <?php endif; ?>
            <?php if (!empty($empleo['fecha_publicacion'])): ?>
              <span class="detail-fecha">Publicado el <?php echo date('d/m/Y', strtotime($empleo['fecha_publicacion'])); ?></span>
            <?php endif; ?>
          </div>
        </div>

        <!-- descripcion -->
        <article class="detail-card">
          <h2>Descripcion del puesto</h2>
          <div class="detail-copy"><?php echo nl2br(htmlspecialchars($empleo['descripcion'] ?? '')); ?></div>
          <?php if (!empty($empleo['tags'])): ?>
            <div class="job-tags detail-tags">
              <?php foreach (array_filter(array_map('trim', explode(',', $empleo['tags']))) as $tag): ?>
                <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>

      </div>

      <!-- sidebar -->
      <aside class="detail-sidebar" id="contacto-anuncio">

        <!-- salario prominente -->
        <?php if (!empty($empleo['salario'])): ?>
        <div class="sidebar-card salary-card">
          <span class="section-kicker">Remuneracion</span>
          <div class="salary-display">S/ <?php echo htmlspecialchars($empleo['salario']); ?></div>
        </div>
        <?php endif; ?>

        <!-- contacto -->
        <div class="sidebar-card contact-card">
          <h4>Contactar con la empresa</h4>
          <div class="contact-empresa-name"><?php echo htmlspecialchars($empleo['empresa'] ?? ''); ?></div>
          <?php if (!empty($empleo['telefono'])): ?>
            <a class="contact-row" href="tel:<?php echo htmlspecialchars($empleo['telefono']); ?>">
              <span class="contact-icon">📞</span>
              <span><?php echo htmlspecialchars($empleo['telefono']); ?></span>
            </a>
          <?php endif; ?>
          <?php if (!empty($empleo['email_contacto'])): ?>
            <a class="contact-row" href="mailto:<?php echo htmlspecialchars($empleo['email_contacto']); ?>">
              <span class="contact-icon">✉️</span>
              <span><?php echo htmlspecialchars($empleo['email_contacto']); ?></span>
            </a>
          <?php endif; ?>
          <?php if (!empty($empleo['telefono'])): ?>
            <a class="btn-primary" style="width:100%;margin-top:16px;justify-content:center" href="tel:<?php echo htmlspecialchars($empleo['telefono']); ?>">Llamar ahora</a>
          <?php elseif (!empty($empleo['email_contacto'])): ?>
            <a class="btn-primary" style="width:100%;margin-top:16px;justify-content:center" href="mailto:<?php echo htmlspecialchars($empleo['email_contacto']); ?>">Enviar correo</a>
          <?php endif; ?>
        </div>

        <!-- resumen -->
        <div class="sidebar-card">
          <h4>Detalles del puesto</h4>
          <dl class="detail-list">
            <div><dt>Empresa</dt><dd><?php echo htmlspecialchars($empleo['empresa'] ?? ''); ?></dd></div>
            <?php if (!empty($empleo['tipo_trabajo'])): ?>
              <div><dt>Tipo de trabajo</dt><dd><?php echo htmlspecialchars($empleo['tipo_trabajo']); ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($empleo['modalidad'])): ?>
              <div><dt>Modalidad</dt><dd><?php echo htmlspecialchars($empleo['modalidad']); ?></dd></div>
            <?php endif; ?>
            <?php if (!empty($empleo['salario'])): ?>
              <div><dt>Salario</dt><dd>S/ <?php echo htmlspecialchars($empleo['salario']); ?></dd></div>
            <?php endif; ?>
          </dl>
        </div>

        <!-- cta publicar -->
        <div class="sidebar-card emphasis-card">
          <h4>Publica tu vacante gratis</h4>
          <p>Llega a mas postulantes con imagen destacada y datos de contacto directos.</p>
          <a class="btn-outline" style="width:100%;justify-content:center;margin-top:12px" href="/publicar-empleo">Publicar anuncio</a>
        </div>

      </aside>
    </div>

  <?php endif; ?>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>