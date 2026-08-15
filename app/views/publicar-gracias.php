<?php require_once 'app/views/layouts/header.php'; ?>
<?php $wizard_step = 5; require 'app/views/partials/wizard.php'; ?>

<div class="container">
    <div class="publish-panel">
        <?php $esDestacado = !empty($publicacion['destacado']) && (int)$publicacion['destacado'] === 1; ?>
        <?php if ($esDestacado): ?>
            <div class="publish-thanks featured">
                <span class="publish-thanks-badge">Pago en revisión</span>
                <h3>Recibimos tu solicitud de anuncio destacado</h3>
                <p class="publish-thanks-copy">
                    Su solicitud de anuncio destacado quedará pendiente por un plazo máximo de 24 horas.
                    Si no se realiza y valida el pago dentro de ese plazo, la solicitud se eliminará automáticamente.
                    Una vez confirmado el pago, su anuncio será aprobado y publicado.
                </p>
                <div class="publish-thanks-steps">
                    <div class="publish-thanks-step is-done">
                        <strong>1. Solicitud registrada</strong>
                        <span>Tu anuncio fue enviado correctamente.</span>
                    </div>
                    <div class="publish-thanks-step is-active">
                        <strong>2. Verificación de pago</strong>
                        <span>El administrador valida el depósito por Yape (máximo 24 horas).</span>
                    </div>
                    <div class="publish-thanks-step">
                        <strong>3. Publicación destacada</strong>
                        <span>Tras validar el pago, el anuncio se aprueba y se publica automáticamente.</span>
                    </div>
                </div>
                <div class="publish-thanks-actions">
                    <a class="btn-primary" href="/mis-anuncios">Ir a mis anuncios</a>
                    <a class="btn-outline" href="/publicar-empleo">Publicar otro anuncio</a>
                </div>
            </div>
        <?php else: ?>
            <div class="publish-thanks standard">
                <span class="publish-thanks-badge">Publicado</span>
                <h3>Tu anuncio ya está visible en la plataforma</h3>
                <p class="publish-thanks-copy">La publicación estándar se activó automáticamente y ya puede recibir postulaciones.</p>
                <div class="publish-thanks-actions">
                    <a class="btn-primary" href="/mis-anuncios">Ver mis anuncios</a>
                    <a class="btn-outline" href="/publicar-empleo">Crear otro anuncio</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>
