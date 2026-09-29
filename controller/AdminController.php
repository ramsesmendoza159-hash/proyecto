<?php
// controller/AdminController.php
// Controlador de administración - Redirige a vistas unificadas
// VERSIÓN CORREGIDA - Admin usa las mismas vistas que operador

require_once __DIR__ . '/../helpers/Controller.php';

class AdminController extends Controller {
    
    public function __construct() {
        parent::__construct();
        
        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
        }
        
        if (!$this->authHelper->isAdmin()) {
            $this->redirect('/dashboard');
        }
    }
    
    /**
     * Dashboard admin
     * URL: /admin/dashboard
     */
    public function dashboard() {
        $this->redirect('/dashboard');
    }
    
    /**
     * Gestión de órdenes - REDIRIGE A VISTA UNIFICADA
     * URL: /admin/gestion_ordenes
     */
    public function gestion_ordenes() {
        // ✅ Redirige al listado unificado de órdenes
        $this->redirect('/ordenes');
    }
    
    /**
     * Gestión de inventario - REDIRIGE A VISTA UNIFICADA
     * URL: /admin/gestion_inventario
     */
    public function gestion_inventario() {
        $this->redirect('/inventario');
    }
    
    /**
     * Gestión de técnicos - REDIRIGE A VISTA UNIFICADA
     * URL: /admin/gestion_tecnicos
     */
    public function gestion_tecnicos() {
        $this->redirect('/tecnicos');
    }
    
    /**
     * Gestión de supervisores - REDIRIGE A VISTA UNIFICADA
     * URL: /admin/gestion_supervisores
     */
    public function gestion_supervisores() {
        $this->redirect('/supervisores');
    }
}
?>
