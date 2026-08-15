<?php require_once 'app/views/layouts/header.php'; ?>
<?php $wizard_step = 3; require 'app/views/partials/wizard.php'; ?>

<div class="container">
    <div class="publish-panel">
        <div class="card" style="padding:18px;border-radius:14px;">
            <?php $d = $_SESSION['post_detalles'] ?? []; ?>

            <h3 style="margin-bottom:12px;">Resumen del anuncio</h3>

            <div style="display:grid;grid-template-columns:180px 1fr;gap:8px 14px;align-items:start;">
                <div><strong>Título:</strong></div>
                <div><?php echo htmlspecialchars($d['titulo'] ?? ''); ?></div>

                <div><strong>Empresa:</strong></div>
                <div><?php echo htmlspecialchars($d['empresa'] ?? ''); ?></div>

                <div><strong>Modalidad:</strong></div>
                <div><?php echo htmlspecialchars($d['modalidad'] ?? ''); ?></div>

                <div><strong>Descripción:</strong></div>
                <div style="white-space:pre-line;"><?php echo htmlspecialchars($d['descripcion'] ?? ''); ?></div>
            </div>

            <form method="post" action="/publicar-revisar" style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap;">
                <?php echo csrf_field(); ?>
                <button class="btn-primary" type="submit">Ir a Revisar</button>
                <a class="btn-outline" href="/publicar-empleo-detalles">Regresar</a>
            </form>
        </div>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
