<div style="font-family:Segoe UI,Arial,sans-serif;color:#1f1a16;line-height:1.55">
    <h2 style="margin:0 0 10px;color:#c64623;">Tu anuncio destacado está en revisión</h2>
    <p>Hola <?php echo htmlspecialchars($name ?? ''); ?>, recibimos tu solicitud de anuncio destacado.</p>
    <p><strong><?php echo htmlspecialchars($titulo ?? ''); ?></strong><?php if (!empty($empresa)): ?> - <?php echo htmlspecialchars($empresa); ?><?php endif; ?></p>
    <p>Tu pago está siendo verificado por el administrador. Te notificaremos cuando sea aprobado y publicado.</p>
    <p>
        <a href="<?php echo htmlspecialchars(($appUrl ?? 'http://localhost') . '/mis-anuncios'); ?>" style="display:inline-block;background:#c64623;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:700;">Ver estado de mi anuncio</a>
    </p>
    <hr style="border:none;border-top:1px solid #eadfce;margin:18px 0;">
    <p style="font-size:12px;color:#6c655d;">Anuncios Lima</p>
</div>
