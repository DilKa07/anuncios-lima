<div style="font-family:Segoe UI,Arial,sans-serif;color:#1f1a16;line-height:1.55">
    <h2 style="margin:0 0 10px;color:#c64623;">Tu anuncio ya está publicado</h2>
    <p>Hola <?php echo htmlspecialchars($name ?? ''); ?>, tu anuncio fue publicado correctamente.</p>
    <p><strong><?php echo htmlspecialchars($titulo ?? ''); ?></strong><?php if (!empty($empresa)): ?> - <?php echo htmlspecialchars($empresa); ?><?php endif; ?></p>
    <p>
        <a href="<?php echo htmlspecialchars($url ?? ($appUrl ?? 'http://localhost')); ?>" style="display:inline-block;background:#c64623;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:700;">Ver anuncio</a>
    </p>
    <hr style="border:none;border-top:1px solid #eadfce;margin:18px 0;">
    <p style="font-size:12px;color:#6c655d;">Anuncios Lima</p>
</div>
