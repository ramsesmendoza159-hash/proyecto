<?php
// views/checklists/reporte.php
// Vista imprimible de un registro de checklist
// ✅ FIX: validar autenticación
// ✅ FIX: sanitizar SVG de firmas (XSS persistente)
// ✅ FIX: rowspan seguro en batch_por_equipo

// ✅ FIX: validar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

if (!isset($registro) || !$registro) {
    die('Registro no disponible');
}

$secciones = $registro['secciones'] ?? [];
$matriz    = $registro['matriz'] ?? [];
$firmas    = $registro['firmas'] ?? [];
$tipo      = $registro['tipo_nombre'] ?? 'Checklist';
$codigo    = $registro['tipo_codigo'] ?? '';
$fecha     = $registro['fecha'] ?? date('Y-m-d');
$turno     = $registro['turno'] ?? null;
$operador  = $registro['operador_nombre'] ?? 'N/A';
$estado    = $registro['estado'] ?? 'BORRADOR';

/**
 * ✅ FIX: sanitizar SVG para prevenir XSS persistente
 */
function sanitizarSVGReporte($svg) {
    if (empty($svg)) return '';
    $svg = preg_replace('#<script[^>]*>.*?</script>#is', '', $svg);
    $svg = preg_replace('#<script[^>]*/?>#i', '', $svg);
    $svg = preg_replace('#\son[a-z]+\s*=\s*"[^"]*"#i', '', $svg);
    $svg = preg_replace("#\son[a-z]+\s*=\s*'[^']*'#i", '', $svg);
    $svg = preg_replace('#javascript\s*:#i', '', $svg);
    $svg = preg_replace('#<foreignObject[^>]*>.*?</foreignObject>#is', '', $svg);
    $svg = preg_replace('#<foreignObject[^>]*/?>#i', '', $svg);
    $svg = preg_replace('#<(iframe|embed|object|link|meta|base)[^>]*>#i', '', $svg);
    return $svg;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte - <?= htmlspecialchars($codigo) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 10px; padding: 5mm; }
            .table { font-size: 9px; }
            .card { box-shadow: none !important; border: 1px solid #ddd !important; page-break-inside: avoid; }
            @page { size: landscape; margin: 8mm; }
        }
        body { background: #fff; padding: 20px; font-family: 'Inter', Arial, sans-serif; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #0d6efd; padding-bottom: 12px; margin-bottom: 18px; }
        .header-left { flex: 1; }
        .header-center { flex: 2; text-align: center; }
        .header-right { flex: 1; text-align: right; font-size: 0.85rem; }
        .header h1 { font-size: 1.4rem; margin: 0; font-weight: 800; color: #0d6efd; }
        .header p { margin: 0; font-size: 0.85rem; }
        .info-line { display: flex; gap: 20px; margin-bottom: 15px; font-size: 0.9rem; flex-wrap: wrap; }
        .info-line span { padding: 4px 10px; background: #f8f9fa; border-radius: 6px; }
        .grilla-reporte { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grilla-reporte th, .grilla-reporte td {
            border: 1px solid #333;
            padding: 3px 5px;
            text-align: center;
            font-size: 0.75rem;
        }
        .grilla-reporte th { background: #e9ecef; font-weight: bold; text-transform: uppercase; }
        .grilla-reporte td.equipo-cell { text-align: left; font-weight: bold; background: #fafafa; }
        .valor-bajo { background: #fff3cd !important; }
        .valor-critico { background: #f8d7da !important; }
        .valor-alto { background: #cfe2ff !important; }
        .firmas-section { margin-top: 25px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 15px; }
        .firma-box { border: 1px solid #333; padding: 10px 20px; text-align: center; min-width: 180px; }
        .firma-box small { display: block; font-size: 0.7rem; color: #555; margin-top: 3px; }
        .firma-svg-container { max-height: 60px; overflow: hidden; margin: 5px 0; }
        .firma-svg-container svg { max-height: 60px; max-width: 160px; }
        .badge-estado { padding: 3px 10px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; text-transform: uppercase; }
        .estado-firmado { background: #d1fae5; color: #065f46; }
        .estado-pendiente { background: #fef3c7; color: #92400e; }
        .estado-rechazado { background: #fee2e2; color: #991b1b; }
        .estado-cerrado { background: #cfe2ff; color: #084298; }
    </style>
</head>
<body>

<!-- Toolbar -->
<div class="no-print text-end mb-3">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print"></i> Imprimir / PDF
    </button>
    <a href="/proyecto/checklists/ver/<?= (int)$registro['id'] ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<!-- Encabezado -->
<div class="header">
    <div class="header-left">
        <p><strong>PROYECTO</strong></p>
        <p>Sistema de Mantenimiento</p>
    </div>
    <div class="header-center">
        <h1><?= htmlspecialchars($tipo) ?></h1>
        <p><strong><?= htmlspecialchars($codigo) ?></strong></p>
    </div>
    <div class="header-right">
        <p>Registro #<?= (int)$registro['id'] ?></p>
        <p>Fecha: <?= date('d/m/Y H:i', strtotime($registro['fecha_creacion'] ?? 'now')) ?></p>
    </div>
</div>

<!-- Info general -->
<div class="info-line">
    <span><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($fecha)) ?></span>
    <?php if (!empty($turno)): ?>
        <span><strong>Turno:</strong> <?= htmlspecialchars($turno) ?></span>
    <?php endif; ?>
    <span><strong>Operador:</strong> <?= htmlspecialchars($operador) ?></span>
    <span>
        <strong>Estado:</strong>
        <span class="badge-estado estado-<?= strtolower($estado) ?>">
            <?= htmlspecialchars($estado) ?>
        </span>
    </span>
</div>

<!-- Renderizar secciones -->
<?php foreach ($secciones as $sec): ?>
    <?php
    $sec_id = (int)$sec['id'];
    $layout = $sec['layout_tipo'] ?? '';
    $equipos_sec = $sec['equipos'] ?? [];
    $campos_sec = $sec['campos'] ?? [];
    ?>
    <div style="margin-bottom: 20px; page-break-inside: avoid;">
        <h5 style="background: #0d6efd; color: #fff; padding: 6px 12px; margin: 0 0 8px 0; font-size: 0.9rem; border-radius: 4px;">
            <?= htmlspecialchars($sec['nombre'] ?? '') ?>
        </h5>

        <?php if ($layout === 'grid_horarios'): ?>
            <?php
            $horarios = [];
            foreach ($matriz as $lec) {
                if ((int)($lec['seccion_id'] ?? 0) === $sec_id && !empty($lec['hora'])) {
                    $horarios[$lec['hora']] = true;
                }
            }
            $horarios = array_keys($horarios);
            sort($horarios);
            ?>
            <table class="grilla-reporte">
                <thead>
                    <tr>
                        <th>EQUIPO</th>
                        <?php foreach ($horarios as $hora): ?>
                            <th><?= substr($hora, 0, 5) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos_sec as $eq):
                        $id_eq = (int)($eq['id_equipo'] ?? 0);
                    ?>
                        <tr>
                            <td class="equipo-cell">
                                <?= htmlspecialchars($eq['nombre_display'] ?? $eq['nombre_equipo'] ?? '') ?>
                            </td>
                            <?php foreach ($horarios as $hora):
                                $campo_id = (int)($campos_sec[0]['id'] ?? 0);
                                $key = $sec_id . '_' . $id_eq . '_' . $hora . '_' . $campo_id . '_0';
                                $lec = $matriz[$key] ?? null;
                                if (!$lec) {
                                    foreach ($matriz as $m) {
                                        if ((int)($m['seccion_id'] ?? 0) === $sec_id
                                            && (int)($m['id_equipo'] ?? 0) === $id_eq
                                            && ($m['hora'] ?? '') === $hora) {
                                            $lec = $m;
                                            break;
                                        }
                                    }
                                }
                                $valor = $lec['valor'] ?? null;
                                $nivel = $lec['nivel_alerta'] ?? 'OK';
                                $cls = '';
                                if ($nivel === 'BAJO') $cls = 'valor-bajo';
                                elseif ($nivel === 'CRITICO') $cls = 'valor-critico';
                                elseif ($nivel === 'ALTO') $cls = 'valor-alto';
                            ?>
                                <td class="<?= $cls ?>">
                                    <?= $valor !== null ? htmlspecialchars($valor) : '—' ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($layout === 'grid_campos'): ?>
            <table class="grilla-reporte">
                <thead>
                    <tr>
                        <th>EQUIPO</th>
                        <?php foreach ($campos_sec as $c): ?>
                            <th><?= htmlspecialchars($c['nombre'] ?? '') ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos_sec as $eq):
                        $id_eq = (int)($eq['id_equipo'] ?? 0);
                    ?>
                        <tr>
                            <td class="equipo-cell">
                                <?= htmlspecialchars($eq['nombre_display'] ?? $eq['nombre_equipo'] ?? '') ?>
                            </td>
                            <?php foreach ($campos_sec as $c):
                                $campo_id = (int)($c['id'] ?? 0);
                                $key = $sec_id . '_' . $id_eq . '__' . $campo_id . '_0';
                                $lec = $matriz[$key] ?? null;
                                if (!$lec) {
                                    foreach ($matriz as $m) {
                                        if ((int)($m['seccion_id'] ?? 0) === $sec_id
                                            && (int)($m['id_equipo'] ?? 0) === $id_eq
                                            && (int)($m['campo_id'] ?? 0) === $campo_id) {
                                            $lec = $m;
                                            break;
                                        }
                                    }
                                }
                                $valor = $lec['valor'] ?? null;
                                $nivel = $lec['nivel_alerta'] ?? 'OK';
                                $cls = '';
                                if ($nivel === 'BAJO') $cls = 'valor-bajo';
                                elseif ($nivel === 'CRITICO') $cls = 'valor-critico';
                                elseif ($nivel === 'ALTO') $cls = 'valor-alto';
                            ?>
                                <td class="<?= $cls ?>">
                                    <?= $valor !== null && $valor !== '' ? htmlspecialchars($valor) : '—' ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($layout === 'checklist_tareas'): ?>
            <table class="grilla-reporte">
                <thead>
                    <tr>
                        <th style="text-align:left;">TAREA</th>
                        <th>R</th>
                        <th>NR</th>
                        <th>OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos_sec as $eq):
                        $id_eq = (int)($eq['id_equipo'] ?? 0);
                        $valor_estado = '';
                        $valor_obs = '';
                        $campo_estado_id = (int)($campos_sec[0]['id'] ?? 0);
                        $campo_obs_id = (int)($campos_sec[1]['id'] ?? 0);
                        foreach ($matriz as $m) {
                            if ((int)($m['seccion_id'] ?? 0) === $sec_id && (int)($m['id_equipo'] ?? 0) === $id_eq) {
                                if ((int)($m['campo_id'] ?? 0) === $campo_estado_id) $valor_estado = $m['valor'];
                                if ((int)($m['campo_id'] ?? 0) === $campo_obs_id) $valor_obs = $m['valor'];
                            }
                        }
                    ?>
                        <tr>
                            <td class="equipo-cell">
                                <?= htmlspecialchars($eq['nombre_display'] ?? $eq['nombre_equipo'] ?? '') ?>
                            </td>
                            <td><?= $valor_estado === 'R' ? '✓' : '' ?></td>
                            <td><?= $valor_estado === 'NR' ? '✓' : '' ?></td>
                            <td style="text-align:left;"><?= htmlspecialchars($valor_obs) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($layout === 'valores_simples'): ?>
            <table class="grilla-reporte">
                <thead>
                    <tr>
                        <th style="text-align:left;">EQUIPO / TANQUE</th>
                        <th>VALOR</th>
                        <th>OBSERVACIONES</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos_sec as $eq):
                        $id_eq = (int)($eq['id_equipo'] ?? 0);
                        $valor_v = '';
                        $valor_o = '';
                        $campo_val_id = (int)($campos_sec[0]['id'] ?? 0);
                        $campo_obs_id = (int)($campos_sec[1]['id'] ?? 0);
                        foreach ($matriz as $m) {
                            if ((int)($m['seccion_id'] ?? 0) === $sec_id && (int)($m['id_equipo'] ?? 0) === $id_eq) {
                                if ((int)($m['campo_id'] ?? 0) === $campo_val_id) $valor_v = $m['valor'];
                                if ((int)($m['campo_id'] ?? 0) === $campo_obs_id) $valor_o = $m['valor'];
                            }
                        }
                    ?>
                        <tr>
                            <td class="equipo-cell">
                                <?= htmlspecialchars($eq['nombre_display'] ?? $eq['nombre_equipo'] ?? '') ?>
                            </td>
                            <td><?= htmlspecialchars($valor_v) ?></td>
                            <td style="text-align:left;"><?= htmlspecialchars($valor_o) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php elseif ($layout === 'batch_por_equipo'): ?>
            <table class="grilla-reporte">
                <thead>
                    <tr>
                        <th style="text-align:left;">CONGELADOR</th>
                        <th>H. INICIO</th>
                        <th>H. FIN</th>
                        <th>N° BATCH</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($equipos_sec as $eq):
                        $id_eq = (int)($eq['id_equipo'] ?? 0);
                        $filas = [];
                        foreach ($matriz as $m) {
                            if ((int)($m['seccion_id'] ?? 0) === $sec_id && (int)($m['id_equipo'] ?? 0) === $id_eq) {
                                $bn = (int)($m['batch_num'] ?? 0);
                                if (!isset($filas[$bn])) $filas[$bn] = [];
                                $filas[$bn][(int)($m['campo_id'] ?? 0)] = $m['valor'];
                            }
                        }
                        ksort($filas);
                        if (empty($filas)) $filas[0] = [];
                        
                        // ✅ FIX: validar que count($filas) >= 1 antes de usar rowspan
                        $total_filas = count($filas);
                        if ($total_filas < 1) $total_filas = 1;
                        
                        $campo_ini = (int)($campos_sec[0]['id'] ?? 0);
                        $campo_fin = (int)($campos_sec[1]['id'] ?? 0);
                        $campo_batch = (int)($campos_sec[2]['id'] ?? 0);
                    ?>
                        <?php $primera = true; foreach ($filas as $bn => $vals): ?>
                            <tr>
                                <?php if ($primera): ?>
                                    <td class="equipo-cell" rowspan="<?= $total_filas ?>">
                                        <?= htmlspecialchars($eq['nombre_display'] ?? $eq['nombre_equipo'] ?? '') ?>
                                    </td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($vals[$campo_ini] ?? '') ?></td>
                                <td><?= htmlspecialchars($vals[$campo_fin] ?? '') ?></td>
                                <td><?= htmlspecialchars($vals[$campo_batch] ?? '') ?></td>
                            </tr>
                        <?php $primera = false; endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>

        <?php else: ?>
            <div class="alert alert-warning">
                Layout no soportado en el reporte: <?= htmlspecialchars($layout) ?>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<!-- Firmas -->
<?php if (!empty($firmas)): ?>
    <div style="margin-top: 30px; page-break-inside: avoid;">
        <h5 style="background: #198754; color: #fff; padding: 6px 12px; margin: 0 0 12px 0; font-size: 0.9rem; border-radius: 4px;">
            FIRMAS
        </h5>
        <div class="firmas-section">
            <?php foreach ($firmas as $f): ?>
                <div class="firma-box">
                    <small style="font-weight:bold; text-transform:uppercase;">
                        <?= htmlspecialchars($f['rol_firmante'] ?? '') ?>
                    </small>
                    <?php if (!empty($f['turno_firma'])): ?>
                        <small style="color:#0d6efd;">Turno: <?= htmlspecialchars($f['turno_firma']) ?></small>
                    <?php endif; ?>

                    <?php if (($f['estado'] ?? '') === 'FIRMADO' && !empty($f['firma_svg'])): ?>
                        <div class="firma-svg-container">
                            <?= sanitizarSVGReporte($f['firma_svg']) ?>
                        </div>
                        <small><?= htmlspecialchars($f['usuario_nombre'] ?? '') ?></small>
                        <small><?= date('d/m/Y H:i', strtotime($f['fecha_firma'] ?? 'now')) ?></small>
                    <?php elseif (($f['estado'] ?? '') === 'RECHAZADO'): ?>
                        <div style="height:60px; display:flex; align-items:center; justify-content:center; color:#dc3545; font-size:0.75rem; font-weight:600;">
                            RECHAZADO
                        </div>
                        <small style="color:#dc3545;"><?= htmlspecialchars($f['motivo_rechazo'] ?? '') ?></small>
                    <?php elseif (($f['estado'] ?? '') === 'OMITIDO'): ?>
                        <div style="height:60px; display:flex; align-items:center; justify-content:center; color:#6c757d; font-size:0.75rem; font-weight:600;">
                            OMITIDO
                        </div>
                    <?php else: ?>
                        <div style="height:60px;"></div>
                        <small style="color:#999;">Pendiente</small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Footer -->
<div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 10px; text-align: center; color: #666; font-size: 0.75rem;">
    <p class="mb-0">Reporte generado automáticamente por el Sistema PROYECTO</p>
    <p class="mb-0"><?= date('d/m/Y H:i:s') ?></p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            window.print();
        }, 800);
    });
</script>

</body>
</html>
