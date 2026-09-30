<?php
// views/layouts/header.php
// ✅ VERSIÓN DEFINITIVA - TEMA AZUL BRILLANTE + FONDO NEGRO
// ✅ CON SOPORTE PARA NOTIFICACIONES TOAST
// ✅ CON BOTONES OUTLINE FONDO BLANCO/TRANSPARENTE

if (!class_exists('SecurityHelper')) {
    require_once __DIR__ . '/../../helpers/SecurityHelper.php';
}
if (!class_exists('AuthHelper')) {
    require_once __DIR__ . '/../../helpers/AuthHelper.php';
}
if (!class_exists('Database')) {
    require_once __DIR__ . '/../../config/database.php';
}
if (!class_exists('ToastHelper')) {
    require_once __DIR__ . '/../../helpers/ToastHelper.php';
}
?>
<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= SecurityHelper::generateCSRFToken() ?>">
    <title><?= $titulo ?? 'Sistema de Mantenimiento' ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        /* ========================================== */
        /* PALETA DE COLORES - MODO CLARO */
        /* ========================================== */
        
        :root {
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --text-white: #ffffff;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --bg-card-hover: #f1f5f9;
            --bg-table-header: #f1f5f9;
            --bg-table-striped: #f8fafc;
            --bg-input: #ffffff;
            --bg-input-disabled: #f1f5f9;
            --bg-alert-info: #d1ecf1;
            --bg-alert-success: #d4edda;
            --bg-alert-danger: #f8d7da;
            --bg-alert-warning: #fff3cd;
            --border-color: #e2e8f0;
            --border-input: #e2e8f0;
            --accent-primary: #0f2d54;
            --accent-hover: #1e3a5f;
            --accent-active: #1e40af;
            --accent-light: rgba(15, 45, 84, 0.1);
            --shadow-color: rgba(15, 23, 42, 0.04);
            --shadow-hover: rgba(15, 23, 42, 0.08);
            --discord-green: #3ba55d;
            --discord-red: #ed4245;
            --discord-yellow: #faa61a;
        }
        
        /* ========================================== */
        /* MODO OSCURO - AZUL BRILLANTE + FONDO NEGRO */
        /* ========================================== */
        
        [data-theme="dark"] {
            --bg-body: #0a0a0f;
            --bg-card: #131320;
            --bg-card-hover: #1c1c30;
            --bg-table-header: #1a1a2e;
            --bg-table-striped: #0f0f1a;
            --bg-input: #0f0f1a;
            --bg-input-disabled: #1a1a2e;
            
            --text-primary: #ffffff;
            --text-secondary: #c9c9d9;
            --text-muted: #8a8aa8;
            --text-white: #ffffff;
            
            --accent-primary: #3b82f6;
            --accent-hover: #2563eb;
            --accent-active: #1d4ed8;
            --accent-light: rgba(59, 130, 246, 0.15);
            --accent-glow: rgba(59, 130, 246, 0.4);
            
            --border-color: #1f1f35;
            --border-input: #2a2a45;
            
            --bg-alert-info: #0c2340;
            --bg-alert-success: #0a2818;
            --bg-alert-danger: #2e0e12;
            --bg-alert-warning: #2e2410;
            
            --shadow-color: rgba(0, 0, 0, 0.5);
            --shadow-hover: rgba(59, 130, 246, 0.15);
            
            --discord-green: #22c55e;
            --discord-red: #ef4444;
            --discord-yellow: #eab308;
        }
        
        /* ========================================== */
        /* APLICACIÓN DE VARIABLES */
        /* ========================================== */
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            background: var(--bg-body);
            font-family: 'Inter', sans-serif;
            color: var(--text-primary);
            transition: background 0.3s ease, color 0.3s ease;
        }
        
        .wrapper {
            display: flex !important;
            min-height: 100vh !important;
            width: 100% !important;
        }
        
        /* ========================================== */
        /* SIDEBAR */
        /* ========================================== */
        
        .sidebar {
            min-height: 100vh !important;
            height: 100% !important;
            width: 260px !important;
            flex-shrink: 0 !important;
            background: var(--bg-card) !important;
            position: sticky !important;
            top: 0 !important;
            overflow-y: auto !important;
            padding: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            z-index: 1000 !important;
            border-right: 1px solid var(--border-color) !important;
            transition: background 0.3s ease, border-color 0.3s ease !important;
        }
        
        .sidebar .brand {
            padding: 24px 20px 20px !important;
            border-bottom: 1px solid var(--border-color) !important;
            text-align: center !important;
        }
        
        .sidebar .brand .brand-icon {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 48px !important;
            height: 48px !important;
            background: var(--accent-primary) !important;
            border-radius: 12px !important;
            font-size: 1.5rem !important;
            color: #fff !important;
            margin-bottom: 12px !important;
        }
        
        .sidebar .brand h4 {
            color: var(--text-primary) !important;
            margin: 0 !important;
            font-weight: 700 !important;
            font-size: 1.1rem !important;
        }
        
        .sidebar .brand small {
            color: var(--text-secondary) !important;
            font-size: 0.7rem !important;
            display: block !important;
            margin-top: 2px !important;
        }
        
        .sidebar .nav-section-title {
            padding: 20px 20px 8px !important;
            font-size: 0.6rem !important;
            text-transform: uppercase !important;
            letter-spacing: 1.5px !important;
            color: var(--text-muted) !important;
            font-weight: 600 !important;
        }
        
        .sidebar .nav {
            flex: 1 !important;
            padding: 8px 0 !important;
            list-style: none !important;
        }
        
        .sidebar .nav-item {
            margin: 2px 12px !important;
            list-style: none !important;
        }
        
        .sidebar .nav-link {
            color: var(--text-secondary) !important;
            padding: 10px 16px !important;
            border-radius: 10px !important;
            transition: all 0.3s ease !important;
            display: flex !important;
            align-items: center !important;
            gap: 12px !important;
            text-decoration: none !important;
            font-weight: 500 !important;
            font-size: 0.85rem !important;
            background: transparent !important;
            border: none !important;
        }
        
        .sidebar .nav-link i {
            width: 20px !important;
            text-align: center !important;
            font-size: 1rem !important;
            color: var(--text-muted) !important;
        }
        
        .sidebar .nav-link:hover {
            background: var(--bg-card-hover) !important;
            color: var(--text-primary) !important;
        }
        
        .sidebar .nav-link:hover i {
            color: var(--text-primary) !important;
        }
        
        .sidebar .nav-link.active {
            background: var(--accent-primary) !important;
            color: #fff !important;
        }
        
        .sidebar .nav-link.active i {
            color: #fff !important;
        }
        
        .sidebar .user-info {
            padding: 16px 20px !important;
            border-top: 1px solid var(--border-color) !important;
            margin-top: auto !important;
        }
        
        .sidebar .user-info .user-details {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            margin-bottom: 10px !important;
        }
        
        .sidebar .user-info .user-avatar {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 36px !important;
            height: 36px !important;
            background: var(--accent-primary) !important;
            border-radius: 50% !important;
            font-weight: 600 !important;
            font-size: 0.85rem !important;
            color: #fff !important;
            flex-shrink: 0 !important;
        }
        
        .sidebar .user-name {
            color: var(--text-primary) !important;
            font-weight: 600 !important;
            font-size: 0.9rem !important;
        }
        
        .sidebar .user-role {
            color: var(--text-muted) !important;
            font-size: 0.7rem !important;
        }
        
        .sidebar .btn-logout {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            width: 100% !important;
            padding: 10px !important;
            background: rgba(237, 66, 69, 0.1) !important;
            color: var(--discord-red) !important;
            border: none !important;
            border-radius: 8px !important;
            text-decoration: none !important;
            font-weight: 500 !important;
            font-size: 0.85rem !important;
            transition: all 0.3s ease !important;
        }
        
        .sidebar .btn-logout:hover {
            background: rgba(237, 66, 69, 0.2) !important;
        }
        
        /* ========================================== */
        /* MAIN CONTENT */
        /* ========================================== */
        
        .main-content {
            flex: 1 !important;
            min-height: 100vh !important;
            display: flex !important;
            flex-direction: column !important;
            background: var(--bg-body) !important;
            transition: background 0.3s ease !important;
        }
        
        .main-content .content {
            flex: 1 !important;
            padding: 24px 30px !important;
        }
        
        /* ========================================== */
        /* TOPBAR */
        /* ========================================== */
        
        .topbar {
            background: var(--bg-card) !important;
            padding: 14px 30px !important;
            border-bottom: 1px solid var(--border-color) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            position: sticky !important;
            top: 0 !important;
            z-index: 999 !important;
            transition: background 0.3s ease, border-color 0.3s ease !important;
        }
        
        .topbar .page-title h4 {
            margin: 0 !important;
            font-weight: 700 !important;
            color: var(--text-primary) !important;
            font-size: 1.2rem !important;
        }
        
        .topbar .page-title small {
            color: var(--text-secondary) !important;
            font-size: 0.75rem !important;
        }
        
        .topbar .user-avatar {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 36px !important;
            height: 36px !important;
            background: var(--accent-primary) !important;
            border-radius: 50% !important;
            font-weight: 600 !important;
            font-size: 0.85rem !important;
            color: #fff !important;
        }
        
        .menu-toggle {
            display: none !important;
            background: transparent !important;
            border: none !important;
            font-size: 1.5rem !important;
            color: var(--text-primary) !important;
            padding: 0 !important;
            cursor: pointer !important;
        }
        
        /* ========================================== */
        /* BOTÓN DE TEMA */
        /* ========================================== */
        
        .btn-theme {
            background: var(--bg-input) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-secondary) !important;
            border-radius: 8px !important;
            padding: 6px 12px !important;
            transition: all 0.3s ease !important;
            cursor: pointer !important;
        }
        
        .btn-theme:hover {
            background: var(--bg-card-hover) !important;
            color: var(--text-primary) !important;
        }
        
        /* ========================================== */
        /* CARDS */
        /* ========================================== */
        
        .card {
            background: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 12px !important;
            box-shadow: 0 2px 8px var(--shadow-color) !important;
            transition: background 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease !important;
        }
        
        .card:hover {
            box-shadow: 0 4px 16px var(--shadow-hover) !important;
        }
        
        .card-header {
            background: var(--bg-table-header) !important;
            border-bottom: 1px solid var(--border-color) !important;
            border-radius: 12px 12px 0 0 !important;
            padding: 16px 20px !important;
        }
        
        .card-body {
            padding: 20px !important;
        }
        
        /* ========================================== */
        /* TABLAS */
        /* ========================================== */
        
        .table {
            color: var(--text-primary) !important;
            background: var(--bg-card) !important;
            border-radius: 12px !important;
            overflow: hidden !important;
        }
        
        .table thead th {
            background: var(--bg-table-header) !important;
            color: var(--text-primary) !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 0.7rem !important;
            letter-spacing: 0.5px !important;
            padding: 12px 16px !important;
            border-bottom: 2px solid var(--border-color) !important;
        }
        
        .table tbody td {
            padding: 12px 16px !important;
            border-bottom: 1px solid var(--border-color) !important;
            color: var(--text-primary) !important;
            background: var(--bg-card) !important;
        }
        
        .table tbody tr:hover td {
            background: var(--bg-card-hover) !important;
        }
        
        .table tbody tr:nth-of-type(even) td {
            background: var(--bg-table-striped) !important;
        }
        
        .table tbody tr:nth-of-type(even):hover td {
            background: var(--bg-card-hover) !important;
        }
        
        .table-wrapper {
            background: var(--bg-card);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }
        
        .table-responsive {
            border-radius: 16px !important;
            overflow: hidden !important;
        }
        
        /* ========================================== */
        /* FORMULARIOS */
        /* ========================================== */
        
        .form-control,
        .form-select {
            background: var(--bg-input) !important;
            border: 1px solid var(--border-input) !important;
            color: var(--text-primary) !important;
            border-radius: 8px !important;
            padding: 10px 14px !important;
            transition: border-color 0.3s ease, box-shadow 0.3s ease !important;
        }
        
        .form-control:focus,
        .form-select:focus {
            border-color: var(--accent-primary) !important;
            box-shadow: 0 0 0 3px var(--accent-light) !important;
        }
        
        .form-control::placeholder {
            color: var(--text-muted) !important;
        }
        
        .form-control:disabled,
        .form-control[readonly] {
            background: var(--bg-input-disabled) !important;
            color: var(--text-secondary) !important;
        }
        
        .form-label {
            color: var(--text-secondary) !important;
            font-weight: 500 !important;
        }
        
        /* ========================================== */
        /* BOTONES - RELLENOS (Acciones principales) */
        /* ========================================== */
        
        .btn-primary {
            background: var(--accent-primary) !important;
            border: none !important;
            color: #fff !important;
            border-radius: 8px !important;
            padding: 10px 20px !important;
            font-weight: 500 !important;
            transition: all 0.3s ease !important;
        }
        
        .btn-primary:hover {
            background: var(--accent-hover) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px var(--accent-light) !important;
        }
        
        .btn-success {
            background: var(--discord-green) !important;
            border: none !important;
            color: #fff !important;
            border-radius: 8px !important;
            padding: 10px 20px !important;
            font-weight: 500 !important;
        }
        
        .btn-success:hover {
            background: #2d8f4e !important;
            transform: translateY(-1px) !important;
        }
        
        .btn-danger {
            background: var(--discord-red) !important;
            border: none !important;
            color: #fff !important;
            border-radius: 8px !important;
            padding: 10px 20px !important;
            font-weight: 500 !important;
        }
        
        .btn-danger:hover {
            background: #c0353a !important;
            transform: translateY(-1px) !important;
        }
        
        .btn-secondary {
            background: var(--bg-input-disabled) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-secondary) !important;
            border-radius: 8px !important;
            padding: 10px 20px !important;
            font-weight: 500 !important;
        }
        
        .btn-secondary:hover {
            background: var(--bg-card-hover) !important;
            color: var(--text-primary) !important;
        }
        
        /* ========================================== */
        /* BOTONES OUTLINE - FONDO BLANCO PURO (modo claro) */
        /* FONDO TRANSPARENTE (modo oscuro) */
        /* Solo borde + ícono de color */
        /* ========================================== */
        
        /* === MODO CLARO: fondo blanco === */
        .btn-outline-primary,
        .btn-outline-secondary,
        .btn-outline-success,
        .btn-outline-danger,
        .btn-outline-warning,
        .btn-outline-info {
            background-color: #ffffff !important;
            background-image: none !important;
            box-shadow: none !important;
            transition: all 0.2s ease !important;
        }
        
        .btn-outline-primary:focus,
        .btn-outline-secondary:focus,
        .btn-outline-success:focus,
        .btn-outline-danger:focus,
        .btn-outline-warning:focus,
        .btn-outline-info:focus {
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15) !important;
        }
        
        .btn-outline-primary:active,
        .btn-outline-secondary:active,
        .btn-outline-success:active,
        .btn-outline-danger:active,
        .btn-outline-warning:active,
        .btn-outline-info:active {
            background-color: #ffffff !important;
            box-shadow: none !important;
        }
        
        .btn-outline-primary:hover,
        .btn-outline-secondary:hover,
        .btn-outline-success:hover,
        .btn-outline-danger:hover,
        .btn-outline-warning:hover,
        .btn-outline-info:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
            transform: translateY(-2px) !important;
        }
        
        /* Colores de borde e ícono (modo claro) */
        .btn-outline-primary { border: 1.5px solid #0d6efd !important; color: #0d6efd !important; }
        .btn-outline-secondary { border: 1.5px solid #6c757d !important; color: #6c757d !important; }
        .btn-outline-success { border: 1.5px solid #198754 !important; color: #198754 !important; }
        .btn-outline-danger { border: 1.5px solid #dc3545 !important; color: #dc3545 !important; }
        .btn-outline-warning { border: 1.5px solid #ffc107 !important; color: #ffc107 !important; }
        .btn-outline-info { border: 1.5px solid #0dcaf0 !important; color: #0dcaf0 !important; }
        
        /* Hover: rellenar solo al pasar el mouse */
        .btn-outline-primary:hover { background-color: #0d6efd !important; color: #ffffff !important; }
        .btn-outline-secondary:hover { background-color: #6c757d !important; color: #ffffff !important; }
        .btn-outline-success:hover { background-color: #198754 !important; color: #ffffff !important; }
        .btn-outline-danger:hover { background-color: #dc3545 !important; color: #ffffff !important; }
        .btn-outline-warning:hover { background-color: #ffc107 !important; color: #000000 !important; }
        .btn-outline-info:hover { background-color: #0dcaf0 !important; color: #ffffff !important; }
        
        /* === MODO OSCURO: fondo transparente === */
        [data-theme="dark"] .btn-outline-primary,
        [data-theme="dark"] .btn-outline-secondary,
        [data-theme="dark"] .btn-outline-success,
        [data-theme="dark"] .btn-outline-danger,
        [data-theme="dark"] .btn-outline-warning,
        [data-theme="dark"] .btn-outline-info {
            background-color: transparent !important;
        }
        
        [data-theme="dark"] .btn-outline-primary { border-color: #60a5fa !important; color: #60a5fa !important; }
        [data-theme="dark"] .btn-outline-secondary { border-color: #8a8aa8 !important; color: #8a8aa8 !important; }
        [data-theme="dark"] .btn-outline-success { border-color: #4ade80 !important; color: #4ade80 !important; }
        [data-theme="dark"] .btn-outline-danger { border-color: #f87171 !important; color: #f87171 !important; }
        [data-theme="dark"] .btn-outline-warning { border-color: #facc15 !important; color: #facc15 !important; }
        [data-theme="dark"] .btn-outline-info { border-color: #22d3ee !important; color: #22d3ee !important; }
        
        [data-theme="dark"] .btn-outline-primary:hover { background-color: #60a5fa !important; color: #0a0a0f !important; }
        [data-theme="dark"] .btn-outline-secondary:hover { background-color: #8a8aa8 !important; color: #0a0a0f !important; }
        [data-theme="dark"] .btn-outline-success:hover { background-color: #4ade80 !important; color: #0a0a0f !important; }
        [data-theme="dark"] .btn-outline-danger:hover { background-color: #f87171 !important; color: #0a0a0f !important; }
        [data-theme="dark"] .btn-outline-warning:hover { background-color: #facc15 !important; color: #0a0a0f !important; }
        [data-theme="dark"] .btn-outline-info:hover { background-color: #22d3ee !important; color: #0a0a0f !important; }
        
        /* ========================================== */
        /* BOTONES DE ACCIÓN EN TABLAS (ESTÁNDAR) */
        /* ========================================== */

        /* Contenedor estándar de acciones en tablas */
        .table-actions {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            flex-wrap: nowrap;
        }

        /* Botón de acción cuadradito 32×32px (solo ícono) */
        .btn-icon {
            width: 32px;
            height: 32px;
            padding: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 8px !important;
            font-size: 0.85rem !important;
            transition: all 0.2s ease !important;
            flex-shrink: 0;
        }

        .btn-icon:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }

        .btn-icon:active {
            transform: translateY(0);
        }

        /* Botones de header con texto */
        .btn-header-action {
            padding: 8px 16px !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            transition: all 0.2s ease !important;
        }

        .btn-header-action:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08) !important;
        }

        /* Compatibilidad: cualquier .btn-sm dentro de tabla mantiene el look */
        .table .btn-sm {
            padding: 6px 12px !important;
            min-width: 36px !important;
            border-radius: 8px !important;
            transition: all 0.2s ease !important;
        }

        .table .btn-icon {
            padding: 0 !important;
            min-width: 32px !important;
        }
        
        /* ========================================== */
        /* BADGES */
        /* ========================================== */
        
        .badge {
            border-radius: 6px !important;
            padding: 4px 12px !important;
            font-weight: 500 !important;
        }
        
        .badge-status {
            padding: 4px 12px !important;
            border-radius: 6px !important;
            font-weight: 500 !important;
            font-size: 0.75rem !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 4px !important;
        }
        
        /* ========================================== */
        /* ALERTAS */
        /* ========================================== */
        
        .alert {
            border-radius: 8px !important;
            border: none !important;
        }
        
        .alert-info {
            background: var(--bg-alert-info) !important;
            color: #0c5460 !important;
        }
        
        .alert-success {
            background: var(--bg-alert-success) !important;
            color: #155724 !important;
        }
        
        .alert-danger {
            background: var(--bg-alert-danger) !important;
            color: #721c24 !important;
        }
        
        .alert-warning {
            background: var(--bg-alert-warning) !important;
            color: #856404 !important;
        }
        
        /* ========================================== */
        /* MODALES */
        /* ========================================== */
        
        .modal-content {
            background: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 12px !important;
        }
        
        .modal-header {
            background: var(--bg-table-header) !important;
            border-bottom: 1px solid var(--border-color) !important;
            border-radius: 12px 12px 0 0 !important;
        }
        
        .modal-footer {
            background: var(--bg-table-header) !important;
            border-top: 1px solid var(--border-color) !important;
            border-radius: 0 0 12px 12px !important;
        }
        
        .modal-title {
            color: var(--text-primary) !important;
        }
        
        /* ========================================== */
        /* DROPDOWN */
        /* ========================================== */
        
        .dropdown-menu {
            background: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            box-shadow: 0 8px 30px var(--shadow-hover) !important;
        }
        
        .dropdown-menu .dropdown-item {
            color: var(--text-primary) !important;
        }
        
        .dropdown-menu .dropdown-item:hover {
            background: var(--bg-card-hover) !important;
            color: var(--text-primary) !important;
        }
        
        .dropdown-menu .dropdown-divider {
            border-color: var(--border-color) !important;
        }
        
        .dropdown-menu .dropdown-header {
            color: var(--text-secondary) !important;
        }
        
        /* ========================================== */
        /* PAGINACIÓN */
        /* ========================================== */
        
        .page-link {
            background: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            color: var(--text-secondary) !important;
        }
        
        .page-link:hover {
            background: var(--bg-card-hover) !important;
            color: var(--text-primary) !important;
        }
        
        .page-item.active .page-link {
            background: var(--accent-primary) !important;
            border-color: var(--accent-primary) !important;
            color: #fff !important;
        }
        
        .page-item.disabled .page-link {
            color: var(--text-muted) !important;
            background: var(--bg-card) !important;
            border-color: var(--border-color) !important;
        }
        
        /* ========================================== */
        /* PROGRESS */
        /* ========================================== */
        
        .progress {
            background: var(--bg-input-disabled) !important;
            border-radius: 10px !important;
            overflow: hidden !important;
        }
        
        .progress-bar {
            background: var(--accent-primary) !important;
        }
        
        /* ========================================== */
        /* INPUT GROUP */
        /* ========================================== */
        
        .input-group-text {
            background: var(--bg-input) !important;
            border: 1px solid var(--border-input) !important;
            color: var(--text-secondary) !important;
        }
        
        /* ========================================== */
        /* NAV TABS */
        /* ========================================== */
        
        .nav-tabs {
            border-bottom-color: var(--border-color) !important;
        }
        
        .nav-tabs .nav-link {
            color: var(--text-secondary) !important;
            border-color: var(--border-color) !important;
        }
        
        .nav-tabs .nav-link.active {
            background: var(--bg-card) !important;
            color: var(--text-primary) !important;
            border-bottom-color: var(--bg-card) !important;
        }
        
        /* ========================================== */
        /* ESTADÍSTICAS */
        /* ========================================== */
        
        .stat-card-mini,
        .stat-card-modern {
            background: var(--bg-card) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 12px !important;
            padding: 16px 20px !important;
            transition: all 0.3s ease !important;
        }
        
        .stat-card-mini:hover,
        .stat-card-modern:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 4px 16px var(--shadow-hover) !important;
        }
        
        .stat-card-mini .stat-number-mini,
        .stat-card-modern .stat-number-modern {
            color: var(--text-primary) !important;
            font-size: 1.5rem !important;
            font-weight: 700 !important;
            line-height: 1.2 !important;
        }
        
        .stat-card-mini .stat-label,
        .stat-card-modern .stat-label-modern {
            color: var(--text-secondary) !important;
            font-size: 0.65rem !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            font-weight: 600 !important;
        }
        
        .stat-card-mini .stat-icon-mini,
        .stat-card-modern .stat-icon-modern {
            width: 44px !important;
            height: 44px !important;
            border-radius: 10px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.2rem !important;
            flex-shrink: 0 !important;
        }
        
        /* ========================================== */
        /* ACCESOS RÁPIDOS */
        /* ========================================== */
        
        .quick-action {
            transition: all 0.3s ease;
            background: var(--bg-card);
            border: 1px solid var(--border-color) !important;
            border-radius: 12px !important;
            padding: 16px !important;
            text-align: center !important;
            cursor: pointer !important;
            height: 100% !important;
        }
        
        .quick-action:hover {
            transform: translateY(-4px) !important;
            box-shadow: 0 8px 25px var(--shadow-hover) !important;
            border-color: var(--accent-primary) !important;
        }
        
        .quick-action i {
            color: var(--accent-primary) !important;
            transition: color 0.3s ease !important;
        }
        
        .quick-action h6 {
            color: var(--text-primary) !important;
            margin-top: 8px !important;
            margin-bottom: 0 !important;
            font-size: 0.8rem !important;
            font-weight: 600 !important;
        }
        
        /* ========================================== */
        /* RESPONSIVE */
        /* ========================================== */
        
        @media (max-width: 992px) {
            .sidebar {
                position: fixed !important;
                left: -280px !important;
                top: 0 !important;
                height: 100vh !important;
                z-index: 1050 !important;
                transition: left 0.3s ease !important;
            }
            
            .sidebar.show {
                left: 0 !important;
            }
            
            .sidebar-overlay {
                display: none !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                bottom: 0 !important;
                background: rgba(0,0,0,0.6) !important;
                z-index: 1040 !important;
            }
            
            .sidebar-overlay.show {
                display: block !important;
            }
            
            .menu-toggle {
                display: block !important;
            }
        }
        
        @media (min-width: 993px) {
            .menu-toggle {
                display: none !important;
            }
        }
        
        @media (max-width: 768px) {
            .main-content .content {
                padding: 16px !important;
            }
            .topbar {
                padding: 12px 16px !important;
            }
        }
        
        /* ========================================== */
        /* MODO OSCURO - MEJORAS ESTÉTICAS */
        /* ========================================== */
        
        [data-theme="dark"] .sidebar {
            background: #0a0a0f !important;
            border-right: 1px solid #1f1f35 !important;
        }
        
        [data-theme="dark"] .sidebar .brand .brand-icon {
            background: linear-gradient(135deg, #3b82f6, #60a5fa) !important;
            box-shadow: 0 0 25px rgba(59, 130, 246, 0.4) !important;
        }
        
        [data-theme="dark"] .sidebar .brand h4 {
            color: #ffffff !important;
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.3) !important;
        }
        
        [data-theme="dark"] .sidebar .brand small {
            color: #8a8aa8 !important;
        }
        
        [data-theme="dark"] .sidebar .nav-link {
            color: #c9c9d9 !important;
        }
        
        [data-theme="dark"] .sidebar .nav-link i {
            color: #6b7280 !important;
            transition: color 0.3s ease !important;
        }
        
        [data-theme="dark"] .sidebar .nav-link:hover {
            background: rgba(59, 130, 246, 0.08) !important;
            color: #60a5fa !important;
            border-left: 3px solid #3b82f6 !important;
            padding-left: 13px !important;
        }
        
        [data-theme="dark"] .sidebar .nav-link:hover i {
            color: #60a5fa !important;
        }
        
        [data-theme="dark"] .sidebar .nav-link.active {
            background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4) !important;
            border-left: none !important;
        }
        
        [data-theme="dark"] .sidebar .nav-link.active i {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .sidebar .nav-section-title {
            color: #6b7280 !important;
        }
        
        [data-theme="dark"] .sidebar .user-info {
            border-top-color: #1f1f35 !important;
        }
        
        [data-theme="dark"] .sidebar .user-name {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .sidebar .user-role {
            color: #8a8aa8 !important;
        }
        
        [data-theme="dark"] .sidebar .btn-logout {
            color: #ef4444 !important;
            background: rgba(239, 68, 68, 0.1) !important;
        }
        
        [data-theme="dark"] .sidebar .btn-logout:hover {
            background: rgba(239, 68, 68, 0.2) !important;
        }
        
        [data-theme="dark"] .topbar {
            background: #0a0a0f !important;
            border-bottom: 1px solid #1f1f35 !important;
            backdrop-filter: blur(10px) !important;
        }
        
        [data-theme="dark"] .btn-theme {
            background: #131320 !important;
            border: 1px solid #2a2a45 !important;
            color: #60a5fa !important;
        }
        
        [data-theme="dark"] .btn-theme:hover {
            background: #1c1c30 !important;
            border-color: #3b82f6 !important;
            color: #93c5fd !important;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3) !important;
        }
        
        [data-theme="dark"] .card {
            background: #131320 !important;
            border: 1px solid #1f1f35 !important;
            transition: all 0.3s ease !important;
        }
        
        [data-theme="dark"] .card:hover {
            border-color: rgba(59, 130, 246, 0.3) !important;
            box-shadow: 0 8px 30px rgba(59, 130, 246, 0.1) !important;
        }
        
        [data-theme="dark"] .card-header {
            background: #1a1a2e !important;
            border-bottom: 1px solid #1f1f35 !important;
        }
        
        [data-theme="dark"] .table {
            background: #131320 !important;
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .table thead th {
            background: #1a1a2e !important;
            color: #93c5fd !important;
            border-bottom: 1px solid #2a2a45 !important;
        }
        
        [data-theme="dark"] .table tbody td {
            background: #131320 !important;
            color: #ffffff !important;
            border-bottom: 1px solid #1f1f35 !important;
        }
        
        [data-theme="dark"] .table tbody tr:hover td {
            background: #1c1c30 !important;
        }
        
        [data-theme="dark"] .table tbody tr:nth-of-type(even) td {
            background: #0f0f1a !important;
        }
        
        [data-theme="dark"] .table tbody tr:nth-of-type(even):hover td {
            background: #1c1c30 !important;
        }
        
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select {
            background: #0f0f1a !important;
            border: 1px solid #2a2a45 !important;
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2) !important;
            background: #131320 !important;
        }
        
        [data-theme="dark"] .form-control::placeholder {
            color: #6b7280 !important;
        }
        
        [data-theme="dark"] .form-select option {
            background: #0f0f1a !important;
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .form-control:disabled,
        [data-theme="dark"] .form-control[readonly] {
            background: #1a1a2e !important;
            color: #c9c9d9 !important;
        }
        
        [data-theme="dark"] .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
            border: none !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3) !important;
        }
        
        [data-theme="dark"] .btn-primary:hover {
            background: linear-gradient(135deg, #60a5fa, #3b82f6) !important;
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5) !important;
            transform: translateY(-2px) !important;
        }
        
        [data-theme="dark"] .badge.bg-primary {
            background: rgba(59, 130, 246, 0.2) !important;
            color: #93c5fd !important;
            border: 1px solid rgba(59, 130, 246, 0.3) !important;
        }
        
        [data-theme="dark"] .badge.bg-success {
            background: rgba(34, 197, 94, 0.15) !important;
            color: #4ade80 !important;
            border: 1px solid rgba(34, 197, 94, 0.3) !important;
        }
        
        [data-theme="dark"] .badge.bg-warning {
            background: rgba(234, 179, 8, 0.15) !important;
            color: #facc15 !important;
            border: 1px solid rgba(234, 179, 8, 0.3) !important;
        }
        
        [data-theme="dark"] .badge.bg-danger {
            background: rgba(239, 68, 68, 0.15) !important;
            color: #f87171 !important;
            border: 1px solid rgba(239, 68, 68, 0.3) !important;
        }
        
        [data-theme="dark"] .badge.bg-info {
            background: rgba(6, 182, 212, 0.15) !important;
            color: #22d3ee !important;
            border: 1px solid rgba(6, 182, 212, 0.3) !important;
        }
        
        [data-theme="dark"] .badge.bg-secondary {
            background: #1f1f35 !important;
            color: #c9c9d9 !important;
        }
        
        [data-theme="dark"] .stat-card-mini,
        [data-theme="dark"] .stat-card-modern {
            background: linear-gradient(135deg, #131320, #1a1a2e) !important;
            border: 1px solid #1f1f35 !important;
            transition: all 0.3s ease !important;
        }
        
        [data-theme="dark"] .stat-card-mini:hover,
        [data-theme="dark"] .stat-card-modern:hover {
            border-color: rgba(59, 130, 246, 0.4) !important;
            box-shadow: 0 10px 40px rgba(59, 130, 246, 0.15) !important;
            transform: translateY(-4px) !important;
        }
        
        [data-theme="dark"] .stat-card-mini .stat-number-mini,
        [data-theme="dark"] .stat-card-modern .stat-number-modern {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .stat-card-mini .stat-label,
        [data-theme="dark"] .stat-card-modern .stat-label-modern {
            color: #8a8aa8 !important;
        }
        
        [data-theme="dark"] .topbar .user-avatar,
        [data-theme="dark"] .sidebar .user-avatar {
            background: linear-gradient(135deg, #3b82f6, #60a5fa) !important;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.4) !important;
        }
        
        [data-theme="dark"] .dropdown-menu {
            background: #131320 !important;
            border: 1px solid #2a2a45 !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.7) !important;
        }
        
        [data-theme="dark"] .dropdown-menu .dropdown-item {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .dropdown-menu .dropdown-item:hover {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #60a5fa !important;
        }
        
        [data-theme="dark"] .dropdown-menu .dropdown-divider {
            border-color: #2a2a45 !important;
        }
        
        [data-theme="dark"] .alert-info {
            background: #0c2340 !important;
            color: #93c5fd !important;
            border-left: 4px solid #3b82f6 !important;
        }
        
        [data-theme="dark"] .alert-success {
            background: #0a2818 !important;
            color: #4ade80 !important;
            border-left: 4px solid #22c55e !important;
        }
        
        [data-theme="dark"] .alert-danger {
            background: #2e0e12 !important;
            color: #f87171 !important;
            border-left: 4px solid #ef4444 !important;
        }
        
        [data-theme="dark"] .alert-warning {
            background: #2e2410 !important;
            color: #facc15 !important;
            border-left: 4px solid #eab308 !important;
        }
        
        [data-theme="dark"] ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }
        
        [data-theme="dark"] ::-webkit-scrollbar-track {
            background: #0a0a0f;
        }
        
        [data-theme="dark"] ::-webkit-scrollbar-thumb {
            background: #2a2a45;
            border-radius: 5px;
        }
        
        [data-theme="dark"] ::-webkit-scrollbar-thumb:hover {
            background: #3b82f6;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.5);
        }
        
        [data-theme="dark"] .text-primary {
            color: #60a5fa !important;
        }
        
        [data-theme="dark"] .text-info {
            color: #22d3ee !important;
        }
        
        [data-theme="dark"] .text-muted {
            color: #8a8aa8 !important;
        }
        
        [data-theme="dark"] h1,
        [data-theme="dark"] h2,
        [data-theme="dark"] h3,
        [data-theme="dark"] h4,
        [data-theme="dark"] h5,
        [data-theme="dark"] h6 {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .fw-bold,
        [data-theme="dark"] .fw-semibold {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .card-body p,
        [data-theme="dark"] .card-body span:not(.badge):not([class*="text-"]):not([class*="bg-"]),
        [data-theme="dark"] .card-body div:not([class*="badge"]):not([class*="alert"]):not([class*="progress"]) {
            color: #ffffff;
        }
        
        [data-theme="dark"] .card-body .fw-semibold,
        [data-theme="dark"] .card-body .fw-bold,
        [data-theme="dark"] .card-body strong,
        [data-theme="dark"] .card-body b {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .card-body label:not(.form-label),
        [data-theme="dark"] .card-body .text-muted,
        [data-theme="dark"] .card-body small {
            color: #8a8aa8 !important;
        }
        
        [data-theme="dark"] .card-body p:not([class*="text-"]),
        [data-theme="dark"] .card-body .mb-0,
        [data-theme="dark"] .card-body .info-value {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .table td,
        [data-theme="dark"] .table td strong,
        [data-theme="dark"] .table td span:not(.badge) {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .table td small,
        [data-theme="dark"] .table td .text-muted {
            color: #8a8aa8 !important;
        }
        
        [data-theme="dark"] .form-control[readonly],
        [data-theme="dark"] .form-control:disabled {
            color: #c9c9d9 !important;
        }
        
        [data-theme="dark"] .info-label {
            color: #8a8aa8 !important;
        }
        
        [data-theme="dark"] .info-value {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .info-sub {
            color: #8a8aa8 !important;
        }
        
        [data-theme="dark"] a {
            color: #60a5fa;
        }
        
        [data-theme="dark"] a:hover {
            color: #93c5fd;
            text-shadow: 0 0 10px rgba(59, 130, 246, 0.3);
        }
        
        [data-theme="dark"] hr {
            border-color: #1f1f35 !important;
            opacity: 1 !important;
        }
        
        [data-theme="dark"] .page-title h4 {
            color: #ffffff !important;
            text-shadow: 0 0 30px rgba(59, 130, 246, 0.2) !important;
        }
        
        [data-theme="dark"] code {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #93c5fd !important;
            padding: 2px 6px !important;
            border-radius: 4px !important;
            border: 1px solid rgba(59, 130, 246, 0.2) !important;
        }
        
        [data-theme="dark"] .quick-action {
            background: #131320 !important;
            border: 1px solid #1f1f35 !important;
        }
        
        [data-theme="dark"] .quick-action:hover {
            background: #1c1c30 !important;
            border-color: rgba(59, 130, 246, 0.4) !important;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15) !important;
        }
        
        [data-theme="dark"] .quick-action i {
            color: #60a5fa !important;
        }
        
        [data-theme="dark"] .quick-action h6 {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .page-link {
            background: #131320 !important;
            border: 1px solid #2a2a45 !important;
            color: #c9c9d9 !important;
        }
        
        [data-theme="dark"] .page-link:hover {
            background: #1c1c30 !important;
            color: #60a5fa !important;
            border-color: #3b82f6 !important;
        }
        
        [data-theme="dark"] .page-item.active .page-link {
            background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
            border-color: #3b82f6 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4) !important;
        }
        
        [data-theme="dark"] .modal-content {
            background: #131320 !important;
            border: 1px solid #2a2a45 !important;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8) !important;
        }
        
        [data-theme="dark"] .modal-header {
            background: #1a1a2e !important;
            border-bottom: 1px solid #2a2a45 !important;
        }
        
        [data-theme="dark"] .modal-footer {
            background: #1a1a2e !important;
            border-top: 1px solid #2a2a45 !important;
        }
        
        [data-theme="dark"] .modal-title {
            color: #ffffff !important;
        }
        
        [data-theme="dark"] .btn-close {
            filter: invert(1) !important;
        }
        
        [data-theme="dark"] .progress {
            background: #1a1a2e !important;
        }
        
        [data-theme="dark"] .progress-bar {
            background: linear-gradient(90deg, #3b82f6, #60a5fa) !important;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.5) !important;
        }
        
        [data-theme="dark"] .input-group-text {
            background: #0f0f1a !important;
            border-color: #2a2a45 !important;
            color: #c9c9d9 !important;
        }
        
        [data-theme="dark"] .form-check-input:checked {
            background-color: #3b82f6 !important;
            border-color: #3b82f6 !important;
            box-shadow: 0 0 10px rgba(59, 130, 246, 0.5) !important;
        }
        
        [data-theme="dark"] .tooltip-inner {
            background: #1a1a2e !important;
            color: #ffffff !important;
            border: 1px solid #3b82f6 !important;
        }
        
        [data-theme="dark"] .nav-tabs .nav-link {
            color: #c9c9d9 !important;
            border-color: #2a2a45 !important;
        }
        
        [data-theme="dark"] .nav-tabs .nav-link.active {
            background: #131320 !important;
            color: #ffffff !important;
            border-bottom-color: #131320 !important;
        }
        
        [data-theme="dark"] .nav-tabs {
            border-bottom-color: #2a2a45 !important;
        }
        
        /* ========================================== */
        /* NOTIFICACIONES TOAST */
        /* ========================================== */
        
        .toast-container {
            position: fixed;
            top: 80px;
            right: 20px;
            z-index: 9999;
            max-width: 380px;
            pointer-events: none;
        }
        
        .toast-custom {
            pointer-events: auto;
            margin-bottom: 12px;
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(10px);
            animation: toastSlideIn 0.3s ease-out;
            position: relative;
        }
        
        @keyframes toastSlideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast-custom .toast-header {
            padding: 12px 16px;
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .toast-custom .toast-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: #fff;
            flex-shrink: 0;
        }
        
        .toast-custom .toast-header strong {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .toast-custom .toast-header small {
            font-size: 0.75rem;
        }
        
        .toast-custom .toast-body {
            padding: 12px 16px;
            font-size: 0.9rem;
            color: var(--text-primary);
            line-height: 1.5;
        }
        
        .toast-custom .btn-close {
            font-size: 0.7rem;
            opacity: 0.5;
            transition: opacity 0.2s;
        }
        
        .toast-custom .btn-close:hover {
            opacity: 1;
        }
        
        .toast-custom .toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: currentColor;
            animation: toastProgress linear forwards;
        }
        
        @keyframes toastProgress {
            from { width: 100%; }
            to { width: 0%; }
        }
        
        .toast-success {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-left: 4px solid #22c55e;
            color: #166534;
        }
        
        .toast-success .toast-icon {
            background: #22c55e;
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
        }
        
        .toast-success .toast-progress {
            background: #22c55e;
        }
        
        .toast-error {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            border-left: 4px solid #ef4444;
            color: #991b1b;
        }
        
        .toast-error .toast-icon {
            background: #ef4444;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }
        
        .toast-error .toast-progress {
            background: #ef4444;
        }
        
        .toast-warning {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border-left: 4px solid #f59e0b;
            color: #92400e;
        }
        
        .toast-warning .toast-icon {
            background: #f59e0b;
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
        }
        
        .toast-warning .toast-progress {
            background: #f59e0b;
        }
        
        .toast-info {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            border-left: 4px solid #3b82f6;
            color: #1e40af;
        }
        
        .toast-info .toast-icon {
            background: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .toast-info .toast-progress {
            background: #3b82f6;
        }
        
        [data-theme="dark"] .toast-success {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.1) 100%);
            color: #4ade80;
            border-left-color: #22c55e;
        }
        
        [data-theme="dark"] .toast-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.15) 0%, rgba(239, 68, 68, 0.1) 100%);
            color: #f87171;
            border-left-color: #ef4444;
        }
        
        [data-theme="dark"] .toast-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.15) 0%, rgba(245, 158, 11, 0.1) 100%);
            color: #fbbf24;
            border-left-color: #f59e0b;
        }
        
        [data-theme="dark"] .toast-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.15) 0%, rgba(59, 130, 246, 0.1) 100%);
            color: #60a5fa;
            border-left-color: #3b82f6;
        }
        
        [data-theme="dark"] .toast-custom .toast-header {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }
        
        [data-theme="dark"] .toast-custom .toast-header strong,
        [data-theme="dark"] .toast-custom .toast-body {
            color: #ffffff;
        }
        
        [data-theme="dark"] .toast-custom .btn-close {
            filter: invert(1);
        }
        
        @media (max-width: 576px) {
            .toast-container {
                left: 10px;
                right: 10px;
                max-width: none;
                top: 70px;
            }
            
            .toast-custom {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        
        <?php 
        $excludeSidebar = ['login', 'auth/login', 'auth/authenticate'];
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        $showSidebar = true;
        foreach ($excludeSidebar as $path) {
            if (strpos($currentPath, $path) !== false) {
                $showSidebar = false;
                break;
            }
        }
        
        if ($showSidebar && isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id'])) {
            include_once __DIR__ . '/sidebar.php';
        }
        ?>
        
        <div class="main-content">
            <div class="topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">
                        <h4><?= $titulo ?? 'Dashboard' ?></h4>
                        <small><?= date('d/m/Y H:i') ?></small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <!-- BOTÓN MODO OSCURO -->
                    <button class="btn-theme" id="themeToggle" title="Cambiar tema">
                        <i class="fas fa-moon" id="themeIcon"></i>
                    </button>
                    
                    <!-- DROPDOWN DE USUARIO -->
                    <div class="dropdown">
                        <button class="btn btn-link text-decoration-none p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="user-avatar">
                                <?= strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)) ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/proyecto/perfil">
                                <i class="fas fa-user-circle me-2"></i> Mi Perfil
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/proyecto/auth/logout">
                                <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="content">