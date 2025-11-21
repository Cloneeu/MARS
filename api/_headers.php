<?php
    // Establecer el tipo de contenido de la respuesta
    header('Content-Type: application/json; charset=utf-8');
    // Permitir solicitudes desde cualquier origen
    header('Access-Control-Allow-Origin: *');
    // Metodos HTTP permitidos
    header('Access-Control-Allow-Methods: GET, POST, DELETE, PUT, PATCH, OPTIONS');
    // Cabeceras permitidas
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') 
    {
        // Responder con 204 para indicar que la comprobacion CORS fue exitosa
        http_response_code(204);
        exit;
    }
?>