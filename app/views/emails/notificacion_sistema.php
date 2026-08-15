<?php
$title = $title ?? 'Notificación del sistema';
$message = $message ?? '';
$meta = (isset($meta) && is_array($meta)) ? $meta : [];
$siteName = $site_name ?? 'Anuncios Lima';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($title); ?></title>
</head>
<body style="margin:0;padding:0;background:#f4efe5;font-family:Segoe UI,Arial,sans-serif;color:#1e1b18;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4efe5;padding:22px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="620" cellpadding="0" cellspacing="0" style="max-width:620px;width:100%;background:#ffffff;border:1px solid #e6d8c3;border-radius:14px;overflow:hidden;">
          <tr>
            <td style="background:#c64623;color:#fff;padding:16px 18px;font-size:20px;font-weight:700;">
              <?php echo htmlspecialchars($siteName); ?>
            </td>
          </tr>
          <tr>
            <td style="padding:22px 18px;">
              <h2 style="margin:0 0 10px;font-size:24px;color:#9f3214;"><?php echo htmlspecialchars($title); ?></h2>
              <p style="margin:0 0 14px;line-height:1.6;color:#4b433b;"><?php echo nl2br(htmlspecialchars($message)); ?></p>

              <?php if (!empty($meta)): ?>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:10px;">
                  <?php foreach ($meta as $k => $v): ?>
                    <tr>
                      <td style="width:220px;border:1px solid #eadfce;background:#fff8ef;padding:8px 10px;font-weight:700;color:#5a4d40;">
                        <?php echo htmlspecialchars((string)$k); ?>
                      </td>
                      <td style="border:1px solid #eadfce;padding:8px 10px;color:#3f352d;">
                        <?php echo htmlspecialchars((string)$v); ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </table>
              <?php endif; ?>

              <p style="margin:16px 0 0;color:#6c655d;line-height:1.5;">Este correo fue enviado automáticamente por <?php echo htmlspecialchars($siteName); ?>.</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
