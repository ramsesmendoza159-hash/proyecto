<?php
// views/almacen/salida_seleccion.php
// Seleccionar producto para salida - VERSIÓN COMPLETA

if (!isset($_SESSION['usuario_id'])) {
    header('Location: /proyecto/auth/login');
    exit;
}

$rol = $_SESSION['rol'] ?? '';
if ($rol !== 'almacen' && $rol !== 'admin') {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    header('Location: /proyecto/dashboard');
    exit;
}

$titulo = 'Registrar Salida - Seleccionar Producto';
$seccion = 'salida_stock';

$productos = $productos ?? [];

include_once __DIR__ . '/../layouts/header.php';
?>

<div class="container-fluid px-0">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                <i class="fas fa-arrow-up text-danger me-2"></i>Registrar Salida de Stock
            </h4>
            <p class="text-muted small mb-0">
                <i class="fas fa-info-circle me-1"></i> Selecciona el producto que quieres retirar
            </p>
        </div>
        <a href="/proyecto/almacen" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i> Volver
        </a>
    </div>

    <!-- Mensajes -->
    <?php if (isset($_SESSION['mensaje']) && !empty($_SESSION['mensaje'])): ?>
        <div class="alert alert-<?= $_SESSION['mensaje_tipo'] ?? 'success' ?> alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i> <?= htmlspecialchars($_SESSION['mensaje']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['mensaje'], $_SESSION['mensaje_tipo']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error']) && !empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($_SESSION['error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Buscador -->
    <div class="card border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control border-start-0" id="searchInput" 
                               placeholder="Buscar producto por nombre o código..." 
                               onkeyup="filtrarProductos()">
                    </div>
                </div>
                <div class="col-md-3">
                    <select class="form-select" id="categoriaFilter" onchange="filtrarProductos()">
                        <option value="">Todas las categorías</option>
                        <?php 
                        $categorias = array_unique(array_column($productos, 'categoria'));
                        foreach ($categorias as $cat): 
                            if (!empty($cat)):
                        ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endif; endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-secondary w-100" onclick="resetFiltros()">
                        <i class="fas fa-undo me-1"></i> Limpiar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de productos -->
    <div class="card border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="productosTable">
                    <thead class="table-light">
                        <tr>
                            <th>Código</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Ubicación</th>
                            <th class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($productos)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay productos disponibles
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($productos as $producto): ?>
                                <tr class="producto-row" 
                                    data-nombre="<?= strtolower(htmlspecialchars($producto['nombre'] ?? '')) ?>"
                                    data-codigo="<?= strtolower(htmlspecialchars($producto['codigo'] ?? '')) ?>"
                                    data-categoria="<?= htmlspecialchars($producto['categoria'] ?? '') ?>">
                                    <td><code><?= htmlspecialchars($producto['codigo'] ?? 'N/A') ?></code></td>
                                    <td><span class="fw-semibold"><?= htmlspecialchars($producto['nombre'] ?? 'N/A') ?></span></td>
                                    <td>
                                        <span class="badge bg-info bg-opacity-10 text-info">
                                            <?= htmlspecialchars($producto['categoria'] ?? 'General') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (($producto['cantidad'] ?? 0) <= 0): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger">Agotado</span>
                                        <?php elseif (($producto['cantidad'] ?? 0) <= 5): ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning"><?= $producto['cantidad'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success bg-opacity-10 text-success"><?= $producto['cantidad'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($producto['ubicacion'] ?? 'N/A') ?></td>
                                    <td class="text-center">
                                        <?php if (($producto['cantidad'] ?? 0) > 0): ?>
                                            <a href="/proyecto/almacen/salida/<?= $producto['id'] ?>" class="btn btn-sm btn-danger">
                                                <i class="fas fa-arrow-up me-1"></i> Registrar Salida
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Sin stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 text-muted small">
                <i class="fas fa-list me-1"></i> Mostrando <span id="totalVisible"><?= count($productos) ?></span> producto(s)
            </div>
        </div>
    </div>

</div>

<!-- Scripts -->
<script>
function filtrarProductos() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const categoria = document.getElementById('categoriaFilter').value;
    const rows = document.querySelectorAll('.producto-row');
    let visible = 0;

    rows.forEach(row => {
        const nombre = row.getAttribute('data-nombre') || '';
        const codigo = row.getAttribute('data-codigo') || '';
        const rowCategoria = row.getAttribute('data-categoria') || '';
        
        const matchSearch = nombre.includes(search) || codigo.includes(search);
        const matchCategoria = categoria === '' || rowCategoria === categoria;
        
        if (matchSearch && matchCategoria) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('totalVisible').textContent = visible;
}

function resetFiltros() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoriaFilter').value = '';
    filtrarProductos();
}
</script>

<?php include_once __DIR__ . '/../layouts/footer.php'; ?>