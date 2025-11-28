<?php
    require_once __DIR__ . '/_headers.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../helpers/send_json.php';

    // Obtener el metodo HTTP 
    $method = $_SERVER['REQUEST_METHOD'];
    // Obtener el ID de la review si se proporciona
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    // Obtener el ID del producto para filtrar reviews
    $id_producto = isset($_GET['id_producto']) ? intval($_GET['id_producto']) : null;

    $conn = get_db_connection();

    switch ($method) 
    {
        case 'POST':
            crear_review($conn);
            break;

        case 'GET':
            obtener_reviews($conn, $id_producto);
            break;
        
        case 'PUT':
            // actualizar_review($conn, $id);
            break;
        
        case 'DELETE':
            // eliminar_review($conn, $id);
            break;

        default:
            send_json([
                'ok' => false,
                'message' => 'Metodo no permitido'
            ], 
            405);
            break;
    }

    /**
     * Funcion para crear una nueva review en la base de datos
     * @param mysqli Una conexion a la base de datos 
     */
    function crear_review($conn)
    {
        // Obtener los datos del body
        $data = json_decode(file_get_contents('php://input'), true);

        // Obtener las variables
        $id_usuario = isset($data['id_usuario']) ? intval($data['id_usuario']) : 0;
        $id_producto = isset($data['id_producto']) ? intval($data['id_producto']) : 0;
        $calificacion = isset($data['calificacion']) ? intval($data['calificacion']) : 0;
        $comentario = trim($data['comentario'] ?? '');

        // Validar campos obligatorios
        if (!$id_usuario || !$id_producto) 
        {
            send_json([
                'ok' => false,
                'message' => 'El id_usuario e id_producto son obligatorios'
            ], 422);
            return;
        }

        // Validar la calificacion
        if ($calificacion < 1 || $calificacion > 5) 
        {
            send_json([
                'ok' => false,
                'message' => 'La calificacion debe estar entre 1 y 5'
            ], 422);
            return;
        }

        // Verificar que el usuario si exista
        $query_usuario = "SELECT id_usuario 
                            FROM usuarios 
                            WHERE id_usuario = ?";
        $stmt_usuario = mysqli_prepare($conn, $query_usuario);
        mysqli_stmt_bind_param($stmt_usuario, 'i', $id_usuario);
        mysqli_stmt_execute($stmt_usuario);
        $result_usuario = mysqli_stmt_get_result($stmt_usuario);
        
        if (!mysqli_fetch_assoc($result_usuario)) 
        {
            mysqli_stmt_close($stmt_usuario);
            send_json([
                'ok' => false,
                'message' => 'El usuario no existe'
            ], 404);
            return;
        }
        mysqli_stmt_close($stmt_usuario);

        // Verificar que el producto exista
        $query_producto = "SELECT id_producto 
                            FROM productos 
                            WHERE id_producto = ?";
        $stmt_producto = mysqli_prepare($conn, $query_producto);
        mysqli_stmt_bind_param($stmt_producto, 'i', $id_producto);
        mysqli_stmt_execute($stmt_producto);
        $result_producto = mysqli_stmt_get_result($stmt_producto);
        
        if (!mysqli_fetch_assoc($result_producto)) 
        {
            mysqli_stmt_close($stmt_producto);
            send_json([
                'ok' => false,
                'message' => 'El producto no existe'
            ], 404);
            return;
        }
        mysqli_stmt_close($stmt_producto);

        // Verificar que el usuario no haya dejado ya una review antes 
        $query_exists = "SELECT id_review 
                            FROM reviews 
                            WHERE id_usuario = ? AND id_producto = ?";
        $stmt_exists = mysqli_prepare($conn, $query_exists);
        mysqli_stmt_bind_param($stmt_exists, 'ii', $id_usuario, $id_producto);
        mysqli_stmt_execute($stmt_exists);
        $result_exists = mysqli_stmt_get_result($stmt_exists);
        
        if (mysqli_fetch_assoc($result_exists)) 
        {
            mysqli_stmt_close($stmt_exists);
            send_json([
                'ok' => false,
                'message' => 'Ya has dejado una review para este producto'
            ], 409);
            return;
        }
        mysqli_stmt_close($stmt_exists);

        // Insertar la review
        $query = "INSERT INTO reviews 
                        (id_usuario, id_producto, calificacion, comentario) 
                        VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) 
        {
            error_log('Error al preparar la consulta: ' . $query . ' @@@ ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al crear la review'
            ], 500);
            return;
        }

        mysqli_stmt_bind_param($stmt, 'iiis', $id_usuario, $id_producto, $calificacion, $comentario);

        if (!mysqli_stmt_execute($stmt))
        {
            send_json([
                'ok' => false,
                'message' => 'No se pudo crear la review'
            ], 500);
            return;
        }

        mysqli_stmt_close($stmt);

        send_json([
            'ok' => true,
            'message' => 'Review creada exitosamente',
        ], 201);
    }

    /**
     * Funcion para obtener las reviews 
     * @param mysqli Una conexion a la base de datos 
     * @param int|null El ID del producto para filtrar reviews (opcional)
     */
    function obtener_reviews($conn, $id_producto)
    {
        // Checar si se proporciono un LIMIT
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : PHP_INT_MAX;
        
        // Checar si se proporciono un OFFSET
        $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;

        // Si se dio un ID de producto, devolver las reviews de ese producto
        if ($id_producto)
        {
            $query = "SELECT r.id_review, r.id_usuario, r.id_producto, r.calificacion, r.comentario, r.fecha,
                             u.nombre AS nombre_usuario
                        FROM reviews r
                        INNER JOIN usuarios u 
                            ON r.id_usuario = u.id_usuario
                        WHERE r.id_producto = ?
                        ORDER BY r.fecha DESC
                        LIMIT ? OFFSET ?";

            $stmt = mysqli_prepare($conn, $query);

            if (!$stmt) 
            {
                error_log('Error al preparar la consulta: ' . $query . ' @@@ ' . mysqli_error($conn));
                send_json([
                    'ok' => false,
                    'message' => 'Error al intentar recuperar las reseñas del producto'
                ], 500);
                return;
            }

            mysqli_stmt_bind_param($stmt, 'iii', $id_producto, $limit, $offset);

            if (!mysqli_stmt_execute($stmt))
            {
                send_json([
                    'ok' => false,
                    'message' => 'No se pudieron recuperar las reseñas'
                ], 500);
                return;
            }

            $result = mysqli_stmt_get_result($stmt);
            $reviews = mysqli_fetch_all($result, MYSQLI_ASSOC);

            if (empty($reviews))
            {
                send_json([
                    'ok' => false,
                    'message' => 'No se encontraron reseñas para este producto'
                ], 404);
            }

            mysqli_stmt_close($stmt);
            mysqli_free_result($result);

            // Obtener el promedio de calificacion del producto
            $query_avg = "SELECT AVG(calificacion) AS promedio, COUNT(*) AS total_reviews 
                            FROM reviews 
                            WHERE id_producto = ?";
            $stmt_avg = mysqli_prepare($conn, $query_avg);
            mysqli_stmt_bind_param($stmt_avg, 'i', $id_producto);
            mysqli_stmt_execute($stmt_avg);
            $result_avg = mysqli_stmt_get_result($stmt_avg);
            $stats = mysqli_fetch_assoc($result_avg);
            mysqli_stmt_close($stmt_avg);
            mysqli_free_result($result_avg);

            send_json([
                'ok' => true,
                'promedio' => $stats['promedio'] ? round((float)$stats['promedio'], 1) : null,
                'total_reviews' => (int)$stats['total_reviews'],
                'data' => $reviews
            ], 200);
            return;
        }

        // Si no, devolver todas las reviews
        $query = "SELECT r.id_review, r.id_usuario, r.id_producto, r.calificacion, r.comentario, r.fecha,
                         u.nombre AS nombre_usuario, p.nombre AS nombre_producto
                    FROM reviews r
                    INNER JOIN usuarios u 
                        ON r.id_usuario = u.id_usuario
                    INNER JOIN productos p 
                        ON r.id_producto = p.id_producto
                    ORDER BY r.fecha DESC
                    LIMIT ? OFFSET ?";

        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) 
        {
            error_log('Error al preparar la consulta: ' . $query . ' @@@ ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al intentar recuperar las reseñas'
            ], 500);
            return;
        }

        mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);

        if (!mysqli_stmt_execute($stmt))
        {
            send_json([
                'ok' => false,
                'message' => 'No se pudieron recuperar las reseñas'
            ], 500);
            return;
        }

        $result = mysqli_stmt_get_result($stmt);
        $reviews = mysqli_fetch_all($result, MYSQLI_ASSOC);

        mysqli_stmt_close($stmt);
        mysqli_free_result($result);

        send_json([
            'ok' => true,
            'data' => $reviews
        ], 200);
    }

?>