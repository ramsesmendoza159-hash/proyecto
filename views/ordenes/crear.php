<?php
// views/ordenes/crear.php
// VERSIÓN COMPLETA - CON FIRMAS REQUERIDAS Y MODO OSCURO
// ✅ FIX: Tanto admin como operador pueden elegir las firmas requeridas

// Verificar sesión
if (!isset($_SESSION['usuario_id']) || empty($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

// ✅ FIX: Solo admin y operador pueden crear órdenes
$rol_actual = $_SESSION['rol'] ?? '';
if (!in_array($rol_actual, ['admin', 'operador'])) {
    $_SESSION['error'] = 'No tienes permisos para crear órdenes';
    header('Location: /proyecto/dashboard');
    exit;
}

// Incluir SecurityHelper para CSRF
require_once __DIR__ . '/../../helpers/SecurityHelper.php';

$titulo = 'Crear Orden de Trabajo';
$seccion = 'crear_orden';

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-plus-circle text-primary me-2"></i>Crear Orden de Trabajo
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Complete todos los campos para crear una nueva orden
            </p>
        </div>
        <a href="/proyecto/ordenes" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Mensajes de error -->
    <?php if (isset($_SESSION['errores']) && !empty($_SESSION['errores'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <ul class="mb-0">
                <?php foreach ($_SESSION['errores'] as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['errores']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> 
            <?php echo htmlspecialchars($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Formulario -->
    <div class="card border-0">
        <div class="card-body">
            <form method="POST" action="/proyecto/ordenes/guardar" id="formOrden">
                <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::generateCSRFToken(); ?>">
                
                <div class="row g-4">
                    <!-- Título -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="titulo" 
                                   placeholder="Título de la orden" required
                                   value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- PLANTA -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Planta</label>
                            <select class="form-select" name="id_planta" id="id_planta">
                                <option value="">Seleccionar...</option>
                                <?php if (isset($plantas) && is_array($plantas) && count($plantas) > 0): ?>
                                    <?php foreach ($plantas as $planta): ?>
                                        <option value="<?php echo $planta['id_planta']; ?>">
                                            <?php echo htmlspecialchars($planta['nombre_planta']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">⚠️ No hay plantas disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- ÁREA -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Área</label>
                            <select class="form-select" name="id_area" id="id_area">
                                <option value="">Seleccionar...</option>
                                <?php if (isset($areas) && is_array($areas) && count($areas) > 0): ?>
                                    <?php foreach ($areas as $area): ?>
                                        <option value="<?php echo $area['id_area']; ?>">
                                            <?php echo htmlspecialchars($area['nombre_area']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">⚠️ No hay áreas disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- EQUIPO -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Equipo</label>
                            <select class="form-select" name="id_equipo" id="id_equipo">
                                <option value="">Seleccionar...</option>
                                <?php if (isset($equipos) && is_array($equipos) && count($equipos) > 0): ?>
                                    <?php foreach ($equipos as $equipo): ?>
                                        <option value="<?php echo $equipo['id_equipo']; ?>">
                                            <?php echo htmlspecialchars($equipo['nombre_equipo']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">⚠️ No hay equipos disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- COMPONENTE -->
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Componente</label>
                            <select class="form-select" name="id_componente" id="id_componente">
                                <option value="">Seleccionar...</option>
                                <?php if (isset($componentes) && is_array($componentes) && count($componentes) > 0): ?>
                                    <?php foreach ($componentes as $componente): ?>
                                        <option value="<?php echo $componente['id_componente']; ?>">
                                            <?php echo htmlspecialchars($componente['nombre_componente']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">⚠️ No hay componentes disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- TÉCNICO PRINCIPAL -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Técnico Principal</label>
                            <select class="form-select" name="tecnico_id" id="tecnico_id">
                                <option value="">Seleccionar...</option>
                                <?php if (isset($tecnicos) && is_array($tecnicos) && count($tecnicos) > 0): ?>
                                    <?php foreach ($tecnicos as $tecnico): ?>
                                        <option value="<?php echo $tecnico['id']; ?>" 
                                                data-tarifa="<?php echo $tecnico['tarifa'] ?? 0; ?>">
                                            <?php echo htmlspecialchars($tecnico['nombre'] . ' (' . ($tecnico['especialidad'] ?? 'General') . ') - S/ ' . number_format($tecnico['tarifa'] ?? 0, 2)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">⚠️ No hay técnicos disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <!-- N° TÉCNICOS -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">N° Técnicos</label>
                            <input type="number" class="form-control" name="n_tecnicos" 
                                   id="n_tecnicos"
                                   placeholder="Cantidad de técnicos" min="1" max="4"
                                   value="<?php echo htmlspecialchars($_POST['n_tecnicos'] ?? 1); ?>"
                                   onchange="mostrarTecnicos(this.value)">
                        </div>
                    </div>

                    <!-- SUPERVISOR -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Supervisor</label>
                            <select class="form-select" name="id_supervisor" id="id_supervisor">
                                <option value="">Seleccionar...</option>
                                <?php if (isset($supervisores) && is_array($supervisores) && count($supervisores) > 0): ?>
                                    <?php foreach ($supervisores as $supervisor): ?>
                                        <option value="<?php echo $supervisor['id']; ?>">
                                            <?php echo htmlspecialchars($supervisor['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">⚠️ No hay supervisores disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- PROVEEDOR -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Proveedor Servicio Terceros</label>
                            <select class="form-select" name="id_proveedor" id="id_proveedor">
                                <option value="">Seleccionar proveedor...</option>
                                <?php if (isset($proveedores) && is_array($proveedores) && count($proveedores) > 0): ?>
                                    <?php foreach ($proveedores as $proveedor): ?>
                                        <option value="<?php echo $proveedor['id']; ?>">
                                            <?php echo htmlspecialchars($proveedor['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">⚠️ No hay proveedores disponibles</option>
                                <?php endif; ?>
                            </select>
                            <small class="text-muted">Si el servicio es realizado por un tercero</small>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Proveedor (Nombre directo)</label>
                            <input type="text" class="form-control" name="proveedor_servicio" 
                                   placeholder="Nombre del proveedor de servicios"
                                   value="<?php echo htmlspecialchars($_POST['proveedor_servicio'] ?? ''); ?>"
                                   onchange="document.getElementById('id_proveedor').value = ''">
                            <small class="text-muted">Si no está en la lista, escriba el nombre</small>
                        </div>
                    </div>
                </div>

                <!-- TÉCNICOS ADICIONALES (dinámico) -->
                <div id="tecnicos_adicionales" class="row g-4 mt-3">
                    <!-- Se llena con JavaScript -->
                </div>

                <div class="row g-4 mt-3">
                    <!-- Prioridad -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Prioridad <span class="text-danger">*</span></label>
                            <select class="form-select" name="prioridad" required>
                                <option value="Baja">Baja</option>
                                <option value="Media" selected>Media</option>
                                <option value="Alta">Alta</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tipo Mantenimiento -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Tipo Mantenimiento</label>
                            <select class="form-select" name="tipo_mantenimiento">
                                <option value="CORRECTIVO">Correctivo</option>
                                <option value="PREVENTIVO">Preventivo</option>
                                <option value="PREDICTIVO">Predictivo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tipo Actividad -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Tipo Actividad</label>
                            <select class="form-select" name="tipo_actividad">
                                <option value="">Seleccionar...</option>
                                <option value="MECANICA">Mecánica</option>
                                <option value="ELECTRICA">Eléctrica</option>
                                <option value="HIDRAULICA">Hidráulica</option>
                                <option value="NEUMATICA">Neumática</option>
                                <option value="REFRIGERACION">Refrigeración</option>
                            </select>
                        </div>
                    </div>

                    <!-- Solicitante -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Solicitante</label>
                            <input type="text" class="form-control" name="solicitante" 
                                   placeholder="Nombre del solicitante"
                                   value="<?php echo htmlspecialchars($_POST['solicitante'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Fecha Inicio -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Fecha Inicio</label>
                            <input type="date" class="form-control" name="fecha_inicio" 
                                   value="<?php echo htmlspecialchars($_POST['fecha_inicio'] ?? date('Y-m-d')); ?>">
                        </div>
                    </div>

                    <!-- Fecha Estimada -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Fecha Estimada</label>
                            <input type="date" class="form-control" name="fecha_estimada" 
                                   value="<?php echo htmlspecialchars($_POST['fecha_estimada'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- Duración -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Duración (horas)</label>
                            <input type="number" step="0.5" class="form-control" name="horas_duracion" 
                                   placeholder="0" min="0" max="24"
                                   value="<?php echo htmlspecialchars($_POST['horas_duracion'] ?? 0); ?>">
                        </div>
                    </div>

                    <!-- Supervisor Solicitante -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Supervisor Solicitante</label>
                            <input type="text" class="form-control" name="supervisor_solicitante" 
                                   placeholder="Nombre del supervisor"
                                   value="<?php echo htmlspecialchars($_POST['supervisor_solicitante'] ?? ''); ?>">
                        </div>
                    </div>

                    <!-- ✅ FIRMAS REQUERIDAS - VISIBLE PARA ADMIN Y OPERADOR -->
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-transparent border-0 pt-3">
                                <h5 class="mb-0 fw-semibold">
                                    <i class="fas fa-signature text-primary me-2"></i>
                                    Firmas requeridas para esta orden
                                </h5>
                                <p class="text-muted small mb-0 mt-1">
                                    Los roles obligatorios siempre firman. Los opcionales puedes marcarlos según necesites.
                                </p>
                            </div>
                            <div class="card-body">
                                
                                <!-- ROLES FIJOS -->
                                <h6 class="fw-semibold text-muted text-uppercase small mb-3">
                                    <i class="fas fa-lock me-1"></i> Roles obligatorios (siempre firman)
                                </h6>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-4">
                                        <div class="rol-fijo-card p-3 rounded-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-lock text-primary"></i>
                                                <strong>Ingeniero</strong>
                                                <span class="badge bg-primary ms-auto">Paso 1</span>
                                            </div>
                                            <small class="text-muted">Revisión y aprobación</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="rol-fijo-card p-3 rounded-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-lock text-primary"></i>
                                                <strong>Técnico</strong>
                                                <span class="badge bg-primary ms-auto">Paso 3</span>
                                            </div>
                                            <small class="text-muted">Ejecución y firma</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="rol-fijo-card p-3 rounded-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="fas fa-lock text-primary"></i>
                                                <strong>Supervisor</strong>
                                                <span class="badge bg-primary ms-auto">Paso 4</span>
                                            </div>
                                            <small class="text-muted">Supervisión final</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- ROLES OPCIONALES -->
                                <h6 class="fw-semibold text-muted text-uppercase small mb-3">
                                    <i class="fas fa-check-circle me-1"></i> Roles opcionales
                                </h6>
                                <div class="row g-3">
                                    <?php 
                                    $firmas_opcionales = [
                                        'paso_2' => ['rol' => 'calidad', 'nombre' => 'Calidad (Pre)', 'desc' => 'Validación pre-ejecución', 'color' => 'info'],
                                        'paso_5' => ['rol' => 'calidad', 'nombre' => 'Calidad (Post)', 'desc' => 'Validación post-ejecución', 'color' => 'info'],
                                        'paso_6' => ['rol' => 'seguridad', 'nombre' => 'Seguridad', 'desc' => 'Validación de seguridad', 'color' => 'danger'],
                                    ];
                                    
                                    foreach ($firmas_opcionales as $key => $info): 
                                    ?>
                                        <div class="col-md-4">
                                            <label for="firma_<?= $key ?>" class="firma-opcional-card p-3 rounded-3 d-block" style="cursor: pointer;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <input class="form-check-input firma-checkbox" 
                                                           type="checkbox" 
                                                           name="firmas_requeridas[]" 
                                                           value="<?= $key ?>" 
                                                           id="firma_<?= $key ?>"
                                                           style="transform: scale(1.3); margin: 0;">
                                                    <strong><?= $info['nombre'] ?></strong>
                                                    <span class="badge bg-<?= $info['color'] ?> ms-auto">
                                                        Paso <?= str_replace('paso_', '', $key) ?>
                                                    </span>
                                                </div>
                                                <small class="text-muted d-block mt-2"><?= $info['desc'] ?></small>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="alert alert-info mt-3 mb-0 small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Nota:</strong> Los roles no marcados se marcarán como 
                                    <code>OMITIDO</code> y no serán requeridos para cerrar la orden.
                                </div>
                                
                            </div>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Descripción del Mantenimiento <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="descripcion_mantenimiento" 
                                      id="descripcion_mantenimiento"
                                      rows="5" placeholder="Describir el mantenimiento a realizar..." required><?php echo htmlspecialchars($_POST['descripcion_mantenimiento'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Pasos -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Pasos a Realizar</label>
                            <textarea class="form-control" name="pasos" rows="3" 
                                      placeholder="Lista de pasos para el mantenimiento..."><?php echo htmlspecialchars($_POST['pasos'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- REPUESTOS -->
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Repuestos y Consumibles</label>
                            <select class="form-select" id="repuesto_id">
                                <option value="">Seleccionar repuesto...</option>
                                <?php if (isset($repuestos) && is_array($repuestos) && count($repuestos) > 0): ?>
                                    <?php foreach ($repuestos as $repuesto): ?>
                                        <option value="<?php echo $repuesto['id']; ?>" 
                                                data-precio="<?php echo $repuesto['precio_unitario'] ?? 0; ?>">
                                            <?php echo htmlspecialchars($repuesto['nombre'] . ' (Stock: ' . ($repuesto['cantidad'] ?? 0) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="">⚠️ No hay repuestos disponibles</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Cantidad</label>
                            <input type="number" class="form-control" id="cantidad_repuesto" 
                                   placeholder="1" min="1" value="1">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Precio Unit. (S/)</label>
                            <input type="number" step="0.01" class="form-control" id="precio_repuesto" 
                                   placeholder="0.00" min="0" value="0">
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="form-group">
                            <label class="form-label fw-semibold">&nbsp;</label>
                            <button type="button" class="btn btn-success w-100" onclick="agregarRepuesto()">
                                <i class="fas fa-plus me-1"></i> Agregar
                            </button>
                        </div>
                    </div>

                    <!-- Lista de repuestos agregados -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Repuestos Agregados</label>
                            <div id="lista_repuestos" class="border rounded p-2" style="min-height: 50px;">
                                <small class="text-muted">No hay repuestos agregados</small>
                            </div>
                        </div>
                        <input type="hidden" name="repuestos_json" id="repuestos_json" value="[]">
                        <input type="hidden" name="costo_repuestos" id="costo_repuestos_hidden" value="0">
                    </div>

                    <!-- Costos -->
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Tarifa Técnico (S/) <span class="text-muted">(Automática)</span></label>
                            <input type="number" step="0.01" class="form-control bg-light" 
                                   id="tarifa_tecnico" name="tarifa_tecnico" readonly
                                   value="0.00">
                            <small class="text-muted">La tarifa se toma automáticamente del técnico seleccionado</small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Costo Repuestos (S/)</label>
                            <input type="number" step="0.01" class="form-control bg-light" 
                                   id="costo_repuestos_mostrar" readonly value="0.00">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="form-label fw-semibold">Costo Total Estimado (S/)</label>
                            <input type="number" step="0.01" class="form-control bg-light fw-bold" 
                                   id="costo_total_mostrar" readonly value="0.00">
                            <input type="hidden" name="costo_total" id="costo_total_hidden" value="0">
                        </div>
                    </div>
                </div>

                <hr>
                <div class="d-flex gap-3">
                    <a href="/proyecto/ordenes" class="btn btn-secondary">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                        <i class="fas fa-save me-1"></i> Guardar Orden
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* ========================================== */
/* ESTILOS BASE */
/* ========================================== */
.form-group {
    margin-bottom: 0;
}
.form-label {
    font-size: 0.85rem;
    margin-bottom: 0.4rem;
    font-weight: 600;
}
.form-control, .form-select {
    border-radius: 10px;
    padding: 10px 14px;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}
.form-control:focus, .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 4px rgba(13, 110, 253, 0.1);
}
.card {
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0,0,0,0.04);
}

/* ========================================== */
/* ROLES FIJOS */
/* ========================================== */
.rol-fijo-card {
    background: var(--bg-input-disabled, #f8f9fa);
    border: 1px solid var(--border-color, #e9ecef);
    transition: all 0.3s ease;
}
.rol-fijo-card:hover {
    border-color: #0d6efd;
}

/* ========================================== */
/* ROLES OPCIONALES */
/* ========================================== */
.firma-opcional-card {
    background: var(--bg-card, #fff);
    border: 1px solid var(--border-color, #e9ecef);
    transition: all 0.3s ease;
    cursor: pointer;
    display: block;
}
.firma-opcional-card:hover {
    background: var(--bg-card-hover, #f8f9fa);
    border-color: #0d6efd !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
}
.firma-opcional-card:has(.firma-checkbox:checked) {
    background: rgba(13, 110, 253, 0.1);
    border-color: #0d6efd !important;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
}
.firma-checkbox:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
.firma-checkbox {
    cursor: pointer;
}

/* ========================================== */
/* MODO OSCURO */
/* ========================================== */
[data-theme="dark"] .rol-fijo-card {
    background: #1a1a2e !important;
    border-color: #2a2a45 !important;
}
[data-theme="dark"] .rol-fijo-card strong {
    color: #ffffff !important;
}
[data-theme="dark"] .rol-fijo-card small {
    color: #8a8aa8 !important;
}

[data-theme="dark"] .firma-opcional-card {
    background: #131320 !important;
    border-color: #2a2a45 !important;
}
[data-theme="dark"] .firma-opcional-card:hover {
    background: #1a1a2e !important;
    border-color: #3b82f6 !important;
}
[data-theme="dark"] .firma-opcional-card:has(.firma-checkbox:checked) {
    background: rgba(59, 130, 246, 0.15) !important;
    border-color: #3b82f6 !important;
}
[data-theme="dark"] .firma-opcional-card strong {
    color: #ffffff !important;
}
[data-theme="dark"] .firma-opcional-card small {
    color: #8a8aa8 !important;
}

/* Textos en modo oscuro */
[data-theme="dark"] h6.text-muted {
    color: #8a8aa8 !important;
}
[data-theme="dark"] .card-header h5 {
    color: #ffffff !important;
}
[data-theme="dark"] .card-header p.text-muted {
    color: #8a8aa8 !important;
}

/* Alertas en modo oscuro */
[data-theme="dark"] .alert-info {
    background: #0c2340 !important;
    color: #93c5fd !important;
    border-left: 4px solid #3b82f6 !important;
}
[data-theme="dark"] .alert-info code {
    background: rgba(59, 130, 246, 0.2) !important;
    color: #93c5fd !important;
    padding: 2px 6px;
    border-radius: 4px;
}

/* Badges en modo oscuro */
[data-theme="dark"] .badge.bg-primary {
    background: rgba(59, 130, 246, 0.25) !important;
    color: #93c5fd !important;
    border: 1px solid rgba(59, 130, 246, 0.4) !important;
}
[data-theme="dark"] .badge.bg-info {
    background: rgba(6, 182, 212, 0.25) !important;
    color: #22d3ee !important;
    border: 1px solid rgba(6, 182, 212, 0.4) !important;
}
[data-theme="dark"] .badge.bg-danger {
    background: rgba(239, 68, 68, 0.25) !important;
    color: #f87171 !important;
    border: 1px solid rgba(239, 68, 68, 0.4) !important;
}

/* Checkbox en modo oscuro */
[data-theme="dark"] .firma-checkbox {
    background-color: #0f0f1a !important;
    border-color: #2a2a45 !important;
}
[data-theme="dark"] .firma-checkbox:checked {
    background-color: #3b82f6 !important;
    border-color: #3b82f6 !important;
}

/* Inputs readonly en modo oscuro */
[data-theme="dark"] .form-control.bg-light {
    background-color: #1a1a2e !important;
    color: #c9c9d9 !important;
}
</style>

<script>
// ==========================================
// MOSTRAR TÉCNICOS ADICIONALES
// ==========================================
function mostrarTecnicos(cantidad) {
    const container = document.getElementById('tecnicos_adicionales');
    container.innerHTML = '';
    
    <?php if (isset($tecnicos) && is_array($tecnicos) && count($tecnicos) > 0): ?>
        for (let i = 2; i <= cantidad && i <= 4; i++) {
            const row = document.createElement('div');
            row.className = 'row g-3 mt-2';
            row.innerHTML = `
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-semibold">Técnico ${i}</label>
                        <select class="form-select" name="tecnico_${i}_id" onchange="validarTecnicosDuplicados()">
                            <option value="">Seleccionar...</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?php echo $tecnico['id']; ?>" data-tarifa="<?php echo $tecnico['tarifa'] ?? 0; ?>">
                                    <?php echo htmlspecialchars($tecnico['nombre'] . ' (' . ($tecnico['especialidad'] ?? 'General') . ') - S/ ' . number_format($tecnico['tarifa'] ?? 0, 2)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label fw-semibold">Tarifa Técnico ${i} (S/)</label>
                        <input type="number" step="0.01" class="form-control bg-light" 
                               name="tarifa_tecnico_${i}" readonly value="0.00">
                    </div>
                </div>
            `;
            container.appendChild(row);
            
            row.querySelector('select').addEventListener('change', function() {
                const tarifa = this.options[this.selectedIndex]?.getAttribute('data-tarifa') || 0;
                const input = row.querySelector(`input[name="tarifa_tecnico_${i}"]`);
                if (input) input.value = parseFloat(tarifa).toFixed(2);
                calcularCostos();
            });
        }
    <?php endif; ?>
}

function validarTecnicosDuplicados() {
    const principal = document.getElementById('tecnico_id');
    const adicionales = document.querySelectorAll('select[name^="tecnico_"]:not([name="tecnico_id"])');
    
    let seleccionados = [];
    
    if (principal.value) {
        seleccionados.push(principal.value);
    }
    
    for (let select of adicionales) {
        if (select.value && select.value !== '') {
            if (seleccionados.includes(select.value)) {
                alert('❌ El mismo técnico no puede ser seleccionado más de una vez.');
                select.value = '';
                select.focus();
                return false;
            }
            seleccionados.push(select.value);
        }
    }
    return true;
}

function cargarTarifaPrincipal() {
    const select = document.getElementById('tecnico_id');
    const tarifaInput = document.getElementById('tarifa_tecnico');
    const selected = select.options[select.selectedIndex];
    
    if (selected && selected.value) {
        const tarifa = selected.getAttribute('data-tarifa') || 0;
        tarifaInput.value = parseFloat(tarifa).toFixed(2);
    } else {
        tarifaInput.value = '0.00';
    }
    calcularCostos();
}

let repuestos_agregados = [];

function agregarRepuesto() {
    const select = document.getElementById('repuesto_id');
    const cantidad = document.getElementById('cantidad_repuesto');
    const precio = document.getElementById('precio_repuesto');
    
    const repuestoId = select.value;
    const repuestoNombre = select.options[select.selectedIndex]?.text || '';
    const cantidadVal = parseInt(cantidad.value) || 1;
    const precioVal = parseFloat(precio.value) || 0;
    
    if (!repuestoId) {
        alert('Selecciona un repuesto primero');
        return;
    }
    
    if (precioVal <= 0) {
        alert('Ingresa un precio unitario válido');
        return;
    }
    
    const existente = repuestos_agregados.find(r => r.id === repuestoId);
    if (existente) {
        existente.cantidad += cantidadVal;
        existente.costo_total = existente.cantidad * existente.precio_unitario;
    } else {
        repuestos_agregados.push({
            id: repuestoId,
            nombre: repuestoNombre,
            cantidad: cantidadVal,
            precio_unitario: precioVal,
            costo_total: cantidadVal * precioVal
        });
    }
    
    actualizarListaRepuestos();
    calcularCostos();
}

function actualizarListaRepuestos() {
    const container = document.getElementById('lista_repuestos');
    const hidden = document.getElementById('repuestos_json');
    
    if (repuestos_agregados.length === 0) {
        container.innerHTML = '<small class="text-muted">No hay repuestos agregados</small>';
        hidden.value = '[]';
        return;
    }
    
    let html = '<table class="table table-sm table-bordered mb-0">';
    html += '<thead><tr><th>Repuesto</th><th>Cantidad</th><th>Precio Unit.</th><th>Costo Total</th><th>Acción</th></tr></thead>';
    html += '<tbody>';
    
    repuestos_agregados.forEach((r, index) => {
        html += `<tr>
            <td>${r.nombre}</td>
            <td>${r.cantidad}</td>
            <td>S/ ${r.precio_unitario.toFixed(2)}</td>
            <td>S/ ${r.costo_total.toFixed(2)}</td>
            <td><button class="btn btn-sm btn-danger" onclick="eliminarRepuesto(${index})">✕</button></td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    container.innerHTML = html;
    hidden.value = JSON.stringify(repuestos_agregados);
}

function eliminarRepuesto(index) {
    repuestos_agregados.splice(index, 1);
    actualizarListaRepuestos();
    calcularCostos();
}

function calcularCostos() {
    let costo_repuestos = 0;
    repuestos_agregados.forEach(r => {
        costo_repuestos += r.costo_total;
    });
    
    const tarifa = parseFloat(document.getElementById('tarifa_tecnico').value) || 0;
    const total = costo_repuestos + tarifa;
    
    document.getElementById('costo_repuestos_mostrar').value = costo_repuestos.toFixed(2);
    document.getElementById('costo_repuestos_hidden').value = costo_repuestos.toFixed(2);
    document.getElementById('costo_total_mostrar').value = total.toFixed(2);
    document.getElementById('costo_total_hidden').value = total.toFixed(2);
}

document.addEventListener('DOMContentLoaded', function() {
    const nTecnicos = document.getElementById('n_tecnicos');
    if (nTecnicos) {
        mostrarTecnicos(nTecnicos.value);
    }
    
    document.getElementById('tecnico_id').addEventListener('change', function() {
        cargarTarifaPrincipal();
        validarTecnicosDuplicados();
    });
    
    document.addEventListener('change', function(e) {
        if (e.target.matches('select[name^="tecnico_"]')) {
            validarTecnicosDuplicados();
        }
    });
    
    cargarTarifaPrincipal();
    calcularCostos();
    
    document.getElementById('repuesto_id').addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        const precio = selected.getAttribute('data-precio') || 0;
        document.getElementById('precio_repuesto').value = precio;
    });
    
    document.querySelector('input[name="proveedor_servicio"]').addEventListener('input', function() {
        if (this.value.trim() !== '') {
            document.getElementById('id_proveedor').value = '';
        }
    });
    
    document.getElementById('id_proveedor').addEventListener('change', function() {
        if (this.value !== '') {
            document.querySelector('input[name="proveedor_servicio"]').value = '';
        }
    });
    
    document.getElementById('formOrden').addEventListener('submit', function(e) {
        if (!validarTecnicosDuplicados()) {
            e.preventDefault();
            return false;
        }
    });
});
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>