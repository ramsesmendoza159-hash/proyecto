<?php
// controller/AdminController.php
// ✅ NOTA: los métodos gestion_*() no están en el router (no se llaman)

require_once __DIR__ . '/../helpers/Controller.php';

class AdminController extends Controller {

    public function __construct() {
        parent::__construct();

        if (!$this->authHelper->isLoggedIn()) {
            $this->redirect('/auth/login');
            return;
        }

        if (!$this->authHelper->isAdmin()) {
            $this->redirect('/dashboard');
            return;
        }
    }

    /**
     * Dashboard admin
     * URL: /admin/dashboard
     */
    public function dashboard() {
        $this->redirect('/dashboard');
        return;
    }

    /**
     * Gestión de órdenes - REDIRIGE A VISTA UNIFICADA
     * URL: /admin/gestion_ordenes
     */
    public function gestion_ordenes() {
        $this->redirect('/ordenes');
        return;
    }

    /**
     * Gestión de inventario - REDIRIGE A VISTA UNIFICADA
     * URL: /admin/gestion_inventario
     */
    public function gestion_inventario() {
        $this->redirect('/inventario');
        return;
    }

    /**
     * Gestión de técnicos - REDIRIGE A VISTA UNIFICADA
     * URL: /admin/gestion_tecnicos
     */
    public function gestion_tecnicos() {
        $this->redirect('/tecnicos');
        return;
    }

    /**
     * Gestión de supervisores - REDIRIGE A VISTA UNIFICADA
     * URL: /admin/gestion_supervisores
     */
    public function gestion_supervisores() {
        $this->redirect('/supervisores');
        return;
    }
}