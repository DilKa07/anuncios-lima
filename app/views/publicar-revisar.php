<?php require_once 'app/views/layouts/header.php'; ?>
<?php $wizard_step = 4; require 'app/views/partials/wizard.php'; ?>

<div class="container">
    <div class="publish-panel">
        <div class="card" style="padding:18px;border-radius:14px;">
            <?php $d = $_SESSION['post_detalles'] ?? []; ?>

            <h3 style="margin-bottom:12px;">Revisión final</h3>

            <div style="display:grid;grid-template-columns:180px 1fr;gap:8px 14px;align-items:start;">
                <div><strong>Título:</strong></div>
                <div><?php echo htmlspecialchars($d['titulo'] ?? ''); ?></div>

                <div><strong>Empresa:</strong></div>
                <div><?php echo htmlspecialchars($d['empresa'] ?? ''); ?></div>

                <div><strong>Descripción:</strong></div>
                <div style="white-space:pre-line;"><?php echo htmlspecialchars($d['descripcion'] ?? ''); ?></div>
            </div>

            <?php $esDestacado = !empty($d['destacado']) && (string)$d['destacado'] === '1'; ?>
            <?php if ($esDestacado): ?>
                <div style="margin-top:16px;padding:14px;border:1px solid #eadfce;border-radius:12px;background:#fff8ef;">
                    <h4 style="margin-bottom:8px;">Pago de anuncio destacado</h4>
                    <p style="margin-bottom:10px;color:#5f5448;">Escanea el QR de Yape para realizar el pago de tu publicación destacada.</p>
                    <img src="/public/assets/images/Yape.jpeg" alt="QR Yape" style="width:220px;max-width:100%;border-radius:10px;border:1px solid #e4d5bf;">
                </div>
            <?php endif; ?>

            <form method="post" action="/publicar-gracias" class="publish-review-form" style="margin-top:16px;">
                <?php echo csrf_field(); ?>

                <div class="publish-review-actions-row">
                    <label class="publish-review-terms">
                        <input type="checkbox" name="acepta" value="1">
                        <strong><span>Acepto términos y condiciones</span></strong>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Eligendi et aliquam deserunt fugiat molestias ad ratione omnis repellat cupiditate iusto. Quibusdam earum ipsa ad. Illo explicabo voluptatem aperiam temporibus repudiandae?
                            lorem ipsum dolor sit amet consectetur adipisicing elit. Eligendi et aliquam deserunt fugiat molestias ad ratione omnis repellat cupiditate iusto. Quibusdam earum ipsa ad. Illo explicabo voluptatem aperiam temporibus repudiandae?
                        </p>
                    </label>
                </div>

                <?php if (!empty($turnstileEnabled)): ?>
                    <?php if (!empty($turnstileSiteKey)): ?>
                        <div class="publish-review-turnstile">
                            <div id="publishTurnstileWidget"></div>
                            <input type="hidden" name="cf-turnstile-response" id="publishTurnstileToken" value="">
                            <small id="publishTurnstileHint" class="publish-turnstile-hint">Confirma que no eres un bot para continuar.</small>
                        </div>

                        <script>
                            (function () {
                                var form = document.querySelector('.publish-review-form');
                                var tokenInput = document.getElementById('publishTurnstileToken');
                                var hint = document.getElementById('publishTurnstileHint');
                                var widgetId = null;
                                var rendered = false;
                                var submitAfterSolve = false;

                                function renderWidget() {
                                    if (!window.turnstile || !form || !tokenInput || rendered) return false;

                                    widgetId = window.turnstile.render('#publishTurnstileWidget', {
                                        sitekey: '<?php echo htmlspecialchars($turnstileSiteKey, ENT_QUOTES, 'UTF-8'); ?>',
                                        theme: 'auto',
                                        size: 'normal',
                                        appearance: 'always',
                                        callback: function (token) {
                                            tokenInput.value = token || '';
                                            if (hint) hint.classList.remove('is-error');
                                            if (submitAfterSolve && tokenInput.value) {
                                                submitAfterSolve = false;
                                                form.submit();
                                            }
                                        },
                                        'expired-callback': function () {
                                            tokenInput.value = '';
                                        },
                                        'error-callback': function (errorCode) {
                                            tokenInput.value = '';
                                            submitAfterSolve = false;
                                            if (hint) {
                                                hint.classList.add('is-error');
                                                hint.textContent = 'No se pudo cargar la verificacion anti-bot. Codigo: ' + (errorCode || 'desconocido') + '. Revisa hostnames y claves de Turnstile en Cloudflare.';
                                            }
                                        }
                                    });

                                    rendered = true;
                                    return true;
                                }

                                function waitAndRender(attempt) {
                                    if (renderWidget()) return;
                                    if (attempt >= 25) {
                                        if (hint) {
                                            hint.classList.add('is-error');
                                            hint.textContent = 'No se pudo cargar la verificacion anti-bot. Revisa bloqueadores del navegador y configuracion de hostnames/claves en Cloudflare Turnstile.';
                                        }
                                        return;
                                    }
                                    setTimeout(function () {
                                        waitAndRender(attempt + 1);
                                    }, 200);
                                }

                                if (document.readyState === 'loading') {
                                    document.addEventListener('DOMContentLoaded', function () { waitAndRender(0); });
                                } else {
                                    waitAndRender(0);
                                }

                                form.addEventListener('submit', function (ev) {
                                    if (tokenInput.value) return;
                                    ev.preventDefault();

                                    if (window.turnstile && widgetId !== null && typeof window.turnstile.execute === 'function') {
                                        submitAfterSolve = true;
                                        try {
                                            window.turnstile.execute(widgetId);
                                            if (hint) {
                                                hint.classList.remove('is-error');
                                                hint.textContent = 'Completa la verificación anti-bot para continuar.';
                                            }
                                            return;
                                        } catch (e) {
                                            submitAfterSolve = false;
                                        }
                                    }

                                    if (hint) hint.classList.add('is-error');
                                });
                            })();
                        </script>
                        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
                    <?php else: ?>
                        <div class="auth-error">La configuración de Turnstile está incompleta. Contacta al administrador.</div>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="publish-review-buttons">
                    <button class="btn-primary" type="submit">Confirmar y Publicar</button>
                    <a class="btn-outline" href="/publicar-avance">Regresar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'app/views/layouts/footer.php'; ?>