<?php
// views/monitoreo/reporte.php
// Reporte imprimible - Formato MTTO R007
// ✅ FIX: sanitizar firmas SVG (antes era XSS vulnerable)

if (!isset($registro) || !$registro) {
    die('Registro no disponible');
}

// ✅ Usar el helper centralizado
require_once __DIR__ . '/../../helpers/SVGSanitizer.php';

$equipos  = $registro['equipos'] ?? [];
$horarios = $registro['horarios'] ?? [];
$matriz   = $registro['matriz'] ?? [];
$firmas   = $registro['firmas'] ?? [];
$unidad   = $registro['unidad_medida'] ?? '%';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Monitoreo - <?= htmlspecialchars($registro['ruta_codigo'] ?? '') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; }
            .table { font-size: 10px; }
            @page { size: landscape; margin: 10mm; }
        }
        body { background: #fff; padding: 20px; font-family: Arial, sans-serif; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
        .header-left { flex: 1; }
        .header-center { flex: 2; text-align: center; }
        .header-right { flex: 1; text-align: right; font-size: 0.9rem; }
        .header h1 { font-size: 1.3rem; margin: 0; font-weight: bold; }
        .header p { margin: 0; font-size: 0.85rem; }
        .info-line { display: flex; gap: 20px; margin-bottom: 10px; font-size: 0.9rem; }
        .grilla-reporte { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .grilla-reporte th, .grilla-reporte td {
            border: 1px solid #000;
            padding: 3px 5px;
            text-align: center;
            font-size: 0.75rem;
        }
        .grilla-reporte th { background: #f0f0f0; font-weight: bold; }
        .grilla-reporte td.equipo-cell { text-align: left; font-weight: bold; background: #fafafa; }
        .valor-bajo { background: #fff3cd; }
        .valor-critico { background: #f8d7da; }
        .firmas-section { margin-top: 25px; display: flex; justify-content: space-between; flex-wrap: wrap; }
        .firma-box { border: 1px solid #000; padding: 8px 20px; text-align: center; min-width: 180px; margin-bottom: 10px; }
        .firma-box small { display: block; font-size: 0.7rem; color: #555; }
        .firma-svg-container { max-height: 60px; overflow: hidden; margin: 5px 0; }
        .firma-svg-container svg { max-height: 60px; max-width: 160px; }
    </style>
</head>
<body>

<!-- Toolbar -->
<div class="no-print text-end mb-3">
    <button onclick="window.print()" class="btn btn-primary">
        <i class="fas fa-print"></i> Imprimir / PDF
    </button>
    <a href="/proyecto/monitoreo/ver/<?= (int)$registro['id'] ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver
    </a>
</div>

<!-- Encabezado -->
<div class="header">
    <div class="header-left">
        <p><strong>LEYENDA</strong></p>
        <p>N.O: Nivel de Operación</p>
        <p>N.P: Nivel en Parada</p>
    </div>
    <div class="header-center">
        <h1>MONITOREO DE NIVEL</h1>
        <p><strong><?= htmlspecialchars($registro['ruta_nombre'] ?? '') ?></strong></p>
    </div>
    <div class="header-right">
        <p><strong><?= htmlspecialchars($registro['ruta_codigo'] ?? '') ?></strong></p>
        <p>Pág 1 de 1</p>
    </div>
</div>

<!-- Info general -->
<div class="info-line">
    <span><strong>Fecha:</strong> <?= date('d/m/Y', strtotime($registro['fecha'])) ?></span>
    <span><strong>Turno:</strong> <?= htmlspecialchars($registro['turno']) ?></span>
    <span><strong>Operador:</strong> <?= htmlspecialchars($registro['operador_nombre'] ?? 'N/A') ?></span>
    <span><strong>Estado:</strong> <?= htmlspecialchars($registro['estado']) ?></span>
</div>

<!-- Grilla -->
<table class="grilla-reporte">
    <thead>
        <tr>
            <th style="width:200px;">UNIDAD</th>
            <?php foreach ($horarios as $h): ?>
                <th><?= htmlspecialchars(substr($h['hora'], 0, 5)) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($equipos as $eq): ?>
            <tr>
                <td class="equipo-cell"><?= htmlspecialchars($eq['nombre_equipo'] ?? '') ?></td>
                <?php foreach ($horarios as $h): ?>
                    <?php
                    $lec = $matriz[(int)$eq['id_equipo']][$h['hora']] ?? null;
                    $valor = $lec['valor'] ?? null;
                    $nivel = $lec['nivel_alerta'] ?? 'OK';
                    $cls = '';
                    if ($nivel === 'BAJO') $cls = 'valor-bajo';
                    if ($nivel === 'CRITICO') $cls = 'valor-critico';
                    ?>
                    <td class="<?= $cls ?>">
                        <?= $valor !== null ? htmlspecialchars($valor) : '' ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Firmas -->
<div class="firmas-section">
    <?php foreach ($firmas as $f): ?>
        <div class="firma-box">
            <small><?= htmlspecialchars($f['rol_firmante']) ?></small>
            <?php if ($f['estado'] === 'FIRMADO' && !empty($f['firma_svg'])): ?>
                <div class="firma-svg-container">
                    <?= SVGSanitizer::sanitize($f['firma_svg']) ?>
                </div>
                <small><?= htmlspecialchars($f['usuario_nombre'] ?? '') ?></small>
                <small><?= date('d/m/Y H:i', strtotime($f['fecha_firma'])) ?></small>
            <?php else: ?>
                <div style="height:60px;"></div>
                <small>Pendiente</small>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>