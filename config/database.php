<?php
    /** 
    *    Función para obtener la conexión a la base de datos :D 
    *    @return mysqli La conexión a la base de datos
    */
    function get_db_connection(): mysqli
    {
        $config = require __DIR__ . '/config.php';
        $db = $config['db'];

        // Para habilitar que si muestre errores  
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        // Hacer la conexion
        $conn = mysqli_connect(
            $db['host'],
            $db['user'], // Nombre del usuario 
            $db['pass'], 
            $db['name'] // Nombre de la DB
        );

        // Por si falla la conexion
        if (!$conn)
        {
            // Para ver el error completo en los logs
            error_log('Error de conexion con la DB: ' . mysqli_connect_error());

            // Mandar el error al cliente :(
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => false,
                'message' => 'Error al conectar con la base de datos'
            ]);

            exit;
        }
        
        mysqli_set_charset($conn, 'utf8mb4');

        return $conn;
    }
?>