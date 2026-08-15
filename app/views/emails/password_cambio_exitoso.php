<div style="font-family:Segoe UI,Arial,sans-serif;color:#1f1a16;line-height:1.55">
    <h2 style="margin:0 0 10px;color:#c64623;">Cambio de contraseña exitoso</h2>
    <p>Hola <?php echo htmlspecialchars($name ?? ''); ?>, tu contraseña fue actualizada correctamente.</p>
    <p>Fecha y hora del cambio: <strong><?php echo htmlspecialchars($changedAt ?? ''); ?></strong></p>
    <p>Si no reconoces este cambio, ingresa de inmediato y modifica tu contraseña.</p>
    <p>
        <a href="<?php echo htmlspecialchars(($appUrl ?? 'http://localhost') . '/mi-cuenta'); ?>" style="display:inline-block;background:#c64623;color:#fff;text-decoration:none;padding:10px 14px;border-radius:8px;font-weight:700;">Revisar mi cuenta</a>
    </p>
    <hr style="border:none;border-top:1px solid #eadfce;margin:18px 0;">
    <p style="font-size:12px;color:#6c655d;">Anuncios Lima</p>
</div>
