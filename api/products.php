<?php
    require_once __DIR__ . '/_headers.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../helpers/send_json.php';

    // Obtener el metodo HTTP 
    $method = $_SERVER['REQUEST_METHOD'];
    // Obtener el ID del producto si se proporciona
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    $conn = get_db_connection();

    switch ($method) {
        case 'GET':
            obtener_productos($conn, $id);
            break;
        
        case 'POST':
            // crear_producto($conn);
            break;
        
        case 'PUT' || 'PATCH':
            // actualizar_producto($conn, $id);
            break;
        
        case 'DELETE':
            // eliminar_producto($conn, $id);
            break;
        default:
            send_json([
                'ok' => false,
                'message' => 'Metodo no permitido o desconocido'
            ], 
            405);
            break;
    }

    /**
     * Funcion para obtener los productos de la base de datos!!!
     * @param mysqli Una conexion a la base de datos 
     * @param int|null El ID del producto a obtener (esto es opcional)
     */
    function obtener_productos($conn, $id)
    {
        // Checar si se proporciono un LIMIT (si no el valor default es 10) :P
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
        
        //Checar si se proporciono un OFFSET (si no el valor default es 0) :O
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

        // Si se dio un ID, devolver SOLO ese producto
        if ($id)
        {
            $query = "SELECT id_producto, nombre, descripcion, precio, imagen, stock 
            FROM productos 
            WHERE id_producto = ?";

            // statement preparado para evitar SQL Injection
            $stmt = mysqli_prepare($conn, $query);

            // Por si falla la preparacion de la consulta :(
            if (!$stmt) {
                // Para ver el error completo en los logs
                error_log('Error al preparar la consulta: ' . $query . ' @@@ ' . mysqli_error($conn));

                // Enviar un mensaje genericon al cliente 
                send_json([
                    'ok' => false,
                    'message' => 'Error al intentar recuperar el producto'
                ], 500);
                return;
            }

            // Unir el parametro id a la consulta 
            mysqli_stmt_bind_param($stmt, 'i', $id);

            // Ejecutar la consulta y checar q si jale
            if (!mysqli_stmt_execute($stmt))
            {
                send_json([
                    'ok' => false,
                    'message' => 'Producto no encontrado'
                ], 500);
            }

            // Obtener el resultado
            $result = mysqli_stmt_get_result($stmt);

            // Convertir el resultado a un arreglo
            $producto = mysqli_fetch_assoc($result);

            mysqli_stmt_close($stmt);
            mysqli_free_result($result);

            // Devolver el resultado de la query
            send_json($producto, 200);
            return;
        }

        // Devolver mas de un producto 
        $query = "SELECT id_producto, nombre, descripcion, precio, imagen, stock 
        FROM productos 
        ORDER BY id_producto ASC
        LIMIT ? OFFSET ?";

        // statement preparado para evitar SQL Injection
        $stmt = mysqli_prepare($conn, $query);

        // Por si falla la preparacion de la consulta :(
        if (!$stmt) {
            // Para ver el error completo en los logs
            error_log('Error al preparar la consulta: ' . $query . ' @@@ ' . mysqli_error($conn));

            // Enviar un mensaje genericon al cliente 
            send_json([
                'ok' => false,
                'message' => 'Error al intentar recuperar productos'
            ], 500);
            return;
        }

        // Unir el parametro id a la consulta 
        mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);

        // Ejecutar la consulta y checar q si jale
        if (!mysqli_stmt_execute($stmt))
        {
            send_json([
                'ok' => false,
                'message' => 'No se pudieron recuperar productos'
            ], 500);
        }

        // Obtener el resultado
        $result = mysqli_stmt_get_result($stmt);

        // Convertir el resultado a un arreglo
        $productos = mysqli_fetch_assoc($result);

        mysqli_stmt_close($stmt);
        mysqli_free_result($result);

        // Devolver el resultado de la query
        send_json($productos, 200);
        return;
    }
?>