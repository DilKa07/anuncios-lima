<?php
// Uso: include 'app/views/partials/wizard.php'; establecer $wizard_step (1-5)
// Clases: active para el paso actual, completed para los anteriores
$step = $wizard_step ?? 1;
?>
<?php
$step = intval($step);
$pct = 0;
if ($step <= 1) $pct = 0;
else $pct = round((($step - 1) / 4) * 100, 2);
$labels = ['CATEGORIA','DETALLAR','AVANCE','REVISAR','GRACIAS'];
?>
<div class="wizard">
    <div class="progress-line"></div>
    <div class="progress-filled" style="width: <?php echo $pct; ?>%;"></div>
    <div class="steps">
        <?php for ($i = 1; $i <= 5; $i++):
            $classes = [];
            if ($i < $step) $classes[] = 'completed';
            if ($i == $step) $classes[] = 'active';
            $labelText = $labels[$i-1];
        ?>
        <div class="step <?php echo implode(' ', $classes); ?>" title="<?php echo $labelText; ?>">
            <div class="circle"><?php echo $i; ?></div>
            <?php if ($i < $step): ?>
                <div class="badge">Completado</div>
            <?php elseif ($i == $step): ?>
                <div class="badge current">Actual</div>
            <?php endif; ?>
            <div class="tooltip"><?php echo $labelText; ?></div>
            <div class="label"><?php echo $labelText; ?></div>
        </div>
        <?php endfor; ?>
    </div>
</div>
