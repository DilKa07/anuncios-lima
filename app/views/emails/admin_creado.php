<div style="font-family:Segoe UI,Arial,sans-serif;color:#1f1a16;line-height:1.55">
    <h2 style="margin:0 0 10px;color:#c64623;">Cuenta administradora creada</h2>
    <p>Hola <?php echo htmlspecialchars($name ?? ''); ?>, se creó una cuenta administradora para ti en Anuncios Lima.</p>
    <p>Por seguridad, usa la contraseña que te entregó el administrador y cámbiala al iniciar sesión.</p>
    <p>
        <a href="<?php echo htmlspecialchars(($appUrl ?? 'http://localhost') . '/login'); ?>" style="display:inline-block;background:#c64623;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:700;">Ingresar a Anuncios Lima</a>
    </p>
    <hr style="border:none;border-top:1px solid #eadfce;margin:18px 0;">
    <p style="font-size:12px;color:#6c655d;">Anuncios Lima</p>
</div>
