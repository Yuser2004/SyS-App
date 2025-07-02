<?php
require_once __DIR__ . '/../models/conexion.php';

function obtenerClientes() {
    global $conexion;
    $sql = "SELECT * FROM clientes";
    $resultado = $conexion->query($sql);
    return $resultado;
}
