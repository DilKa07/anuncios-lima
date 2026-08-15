<div style="font-family:Segoe UI,Arial,sans-serif;color:#1f1a16;line-height:1.55">
    <h2 style="margin:0 0 10px;color:#c64623;">Bienvenido a Anuncios Lima</h2>
    <p>Hola <?php echo htmlspecialchars($name ?? ''); ?>, tu cuenta fue creada correctamente.</p>
    <p>Desde ahora puedes publicar anuncios y gestionar tus postulaciones.</p>
    <p>
        <a href="<?php echo htmlspecialchars(($appUrl ?? 'http://localhost') . '/login'); ?>" style="display:inline-block;background:#c64623;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:700;">Iniciar sesión</a>
    </p>
    <hr style="border:none;border-top:1px solid #eadfce;margin:18px 0;">
    <p style="font-size:12px;color:#6c655d;">Anuncios Lima</p>
</div>
