<?php
// Funciones de notificaciones para la tienda gamer
// Este archivo es incluido por client/ y admin/ para manejar notificaciones

if (!function_exists('agregarNotificacion')) {
    function agregarNotificacion($conn, $id_usuario, $titulo, $mensaje, $tipo = 'info') {
        return registrar_notificacion($conn, $id_usuario, $titulo, $mensaje, $tipo);
    }
}
