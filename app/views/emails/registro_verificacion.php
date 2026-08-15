<div style="font-family:Inter,Arial,sans-serif;color:#111;line-height:1.4">
    <h2>Bienvenido a Anuncios Lima</h2>
    <p>Hola <?php echo htmlspecialchars($name ?? ''); ?>,</p>
    <p>Gracias por registrarte. Para activar tu cuenta, haz clic en el siguiente enlace:</p>
    <p><a href="<?php echo htmlspecialchars($verifyUrl); ?>">Verificar mi correo</a></p>
    <p>Si no solicitaste este correo, puedes ignorarlo.</p>
    <hr>
    <p style="color:#6b7280;font-size:12px">Anuncios Lima</p>
</div>
