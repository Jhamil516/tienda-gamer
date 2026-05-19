<?php
// Archivo de protección de rutas
// Incluir después de session_start() y cargar funciones.php

if (!function_exists('requerirLogin')) {
    function requerirLogin() {
        if (!isset($_SESSION['id_usuario']) || empty($_SESSION['id_usuario'])) {
            header('Location: ' . BASE_URL . 'auth/login.php');
            exit;
        }
    }
}

if (!function_exists('requerirCliente')) {
    function requerirCliente() {
        requerirLogin();
        // Clientes, empleados y admins pueden acceder al área de cliente
    }
}

if (!function_exists('requerirEmpleado')) {
    function requerirEmpleado() {
        requerirLogin();
        if (!in_array($_SESSION['rol'] ?? '', ['admin', 'empleado'])) {
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }
    }
}

if (!function_exists('requerirAdmin')) {
    function requerirAdmin() {
        requerirLogin();
        if (($_SESSION['rol'] ?? '') !== 'admin') {
            header('Location: ' . BASE_URL . 'index.php');
            exit;
        }
    }
}
