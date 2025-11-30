<?php
    require_once __DIR__ . '/_headers.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../helpers/send_json.php';

    // Obtener el metodo HTTP
    $method = $_SERVER['REQUEST_METHOD'];
    // Obtener la accion solicitada
    $action = $_GET['action'] ?? '';
    // Obtener el ID de la orden si se proporciona
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;

    $conn = get_db_connection();

    switch ($action) 
    {
        case 'create':
            if ($method !== 'POST') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            crear_orden($conn);
            break;

        case 'list':
            if ($method !== 'GET') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            obtener_ordenes($conn, $id);
            break;
        
        case 'update-status':
            if ($method !== 'PUT') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            actualizar_estado_orden($conn, $id);
            break;
        
        case 'cancel':
            if ($method !== 'POST') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            cancelar_orden($conn, $id);
            break;
        
        case 'my-orders':
            if ($method !== 'GET') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            obtener_mis_ordenes($conn);
            break;

        case 'total-sales':
            if ($method !== 'GET') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            obtener_total_ventas($conn);
            break;

        default:
            send_json([
                'ok' => false,
                'message' => 'Accion no valida'
            ], 400);
            break;
    }

    /**
     * Funcion para crear una nueva orden
     * @param mysqli La conexion a la base de datos
     */
    function crear_orden($conn)
    {
        $usuario = obtener_usuario_actual($conn);

        if (!$usuario) 
        {
            send_json([
                'ok' => false,
                'message' => 'No autorizado'
            ], 401);
            return;
        }

        // Obtener el input
        $inputs = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) 
        {
            send_json([
                'ok' => false,
                'message' => 'JSON invalido'
            ], 400);
            return;
        }

        $productos = $inputs['productos'] ?? [];

        // Validar que haya productos
        if (empty($productos) || !is_array($productos)) 
        {
            send_json([
                'ok' => false,
                'message' => 'Se requiere al menos un producto'
            ], 422);
            return;
        }

        // Validar que cada producto tenga id y cantidad
        foreach ($productos as $producto) 
        {
            if (!isset($producto['id_producto']) || !isset($producto['cantidad'])) 
            {
                send_json([
                    'ok' => false,
                    'message' => 'Cada producto debe tener id_producto y cantidad'
                ], 422);
                return;
            }

            if ($producto['cantidad'] <= 0) 
            {
                send_json([
                    'ok' => false,
                    'message' => 'La cantidad solicitada debe ser mayor a 0'
                ], 422);
                return;
            }
        }

        // Para poder revertir la DB hasta este punto en caso de algun error!!!
        mysqli_begin_transaction($conn);

        try 
        {
            // Crear la orden
            $query_orden = "INSERT INTO ordenes (id_usuario, estado) VALUES (?, 'pendiente')";
            $stmt = mysqli_prepare($conn, $query_orden);

            if (!$stmt) 
            {
                throw new Exception('Error al preparar consulta de orden: ' . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmt, 'i', $usuario['id_usuario']);
            
            if (!mysqli_stmt_execute($stmt)) 
            {
                throw new Exception('Error al crear la orden: ' . mysqli_error($conn));
            }

            // Obtener el ID de la orden creada
            $id_orden = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            // Insertar los detalles de la orden
            $query_detalle = "INSERT INTO detalles_orden (id_orden, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)";
            $stmt_detalle = mysqli_prepare($conn, $query_detalle);

            if (!$stmt_detalle) 
            {
                throw new Exception('Error al preparar consulta de detalle: ' . mysqli_error($conn));
            }

            // Procesar cada producto
            foreach ($productos as $producto) 
            {
                $id_producto = intval($producto['id_producto']);
                $cantidad = intval($producto['cantidad']);

                // Verificar que el producto existe y obtener su precio y stock
                $query_producto = "SELECT precio, stock FROM productos WHERE id_producto = ?";
                $stmt_prod = mysqli_prepare($conn, $query_producto);

                if (!$stmt_prod) 
                {
                    throw new Exception('Error al preparar consulta de producto: ' . mysqli_error($conn));
                }

                mysqli_stmt_bind_param($stmt_prod, 'i', $id_producto);
                mysqli_stmt_execute($stmt_prod);
                $result_prod = mysqli_stmt_get_result($stmt_prod);
                $prod_info = mysqli_fetch_assoc($result_prod);
                mysqli_stmt_close($stmt_prod);

                if (!$prod_info) 
                {
                    throw new Exception("Producto con ID $id_producto no encontrado");
                }

                // Verificar stock disponible
                if ($prod_info['stock'] < $cantidad) 
                {
                    throw new Exception("Stock insuficiente para el producto con ID $id_producto. Disponible: {$prod_info['stock']}");
                }

                $precio_unitario = $prod_info['precio'];

                // Insertar los detalles de la orden
                mysqli_stmt_bind_param($stmt_detalle, 'iiid', $id_orden, $id_producto, $cantidad, $precio_unitario);
                
                if (!mysqli_stmt_execute($stmt_detalle)) 
                {
                    throw new Exception('Error al insertar los siguientes detalles: ' . mysqli_error($conn));
                }

                // Actualizar el stock del producto
                $query_stock = "UPDATE productos SET stock = stock - ? WHERE id_producto = ?";
                $stmt_stock = mysqli_prepare($conn, $query_stock);
                mysqli_stmt_bind_param($stmt_stock, 'ii', $cantidad, $id_producto);
                
                if (!mysqli_stmt_execute($stmt_stock)) 
                {
                    throw new Exception('Error al actualizar stock: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt_stock);
            }

            mysqli_stmt_close($stmt_detalle);

            // Hacer commit para guardar los cambios en la DB
            mysqli_commit($conn);

            // Obtener los detalles de la orden creada
            $detalles = obtener_detalles_orden($conn, $id_orden);

            send_json([
                'ok' => true,
                'message' => 'Orden creada exitosamente',
                'data' => [
                    'id_orden' => $id_orden,
                    'estado' => 'pendiente',
                    'detalles' => $detalles,
                    'total' => calcular_total_orden($detalles)
                ]
            ], 201);

        } 
        catch (Exception $e) 
        {
            // Si falla algo hay que revertir los cambios
            mysqli_rollback($conn);
            error_log($e->getMessage());
            send_json([
                'ok' => false,
                'message' => 'Error al crear la orden: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Funcion para obtener todas las ordenes 
     * Esto solo para admins :P
     * @param mysqli La conexion a la base de datos
     */
    function obtener_ordenes($conn)
    {
        $usuario = obtener_usuario_actual($conn);

        if (!$usuario) 
        {
            send_json([
                'ok' => false,
                'message' => 'No autorizado'
            ], 401);
            return;
        }

        // Verficar q sea admin
        if ($usuario['rol'] !== 'admin') 
        {
            send_json([
                'ok' => false,
                'message' => 'No tienes permiso para ver las ordenes :('
            ], 403);
            return;
        }

        $query = "SELECT o.id_orden, o.id_usuario, o.created_at, o.estado,
                         u.nombre, u.apellido, u.email
                  FROM ordenes o
                  INNER JOIN usuarios u ON o.id_usuario = u.id_usuario";

        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) 
        {
            error_log('Error al preparar consulta de ordenes: ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al obtener las ordenes'
            ], 500);
            return;
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $ordenes = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        // Agregar el total a cada orden, se utiliza referencia para modificar el array original y que se guarde en este
        foreach ($ordenes as &$orden) 
        {
            $detalles = obtener_detalles_orden($conn, $orden['id_orden']);
            $orden['detalles'] = $detalles;
            $orden['total'] = calcular_total_orden($detalles);
        }

        send_json([
            'ok' => true,
            'data' => $ordenes
        ], 200);
    }

    /**
     * Funcion para obtener las ordenes del usuario actual
     * @param mysqli La conexion a la base de datos
     */
    function obtener_mis_ordenes($conn)
    {
        $usuario = obtener_usuario_actual($conn);

        if (!$usuario) 
        {
            send_json([
                'ok' => false,
                'message' => 'No autorizado'
            ], 401);
            return;
        }

        $query = "SELECT id_orden, created_at, estado
                  FROM ordenes
                  WHERE id_usuario = ?";

        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) 
        {
            error_log('Error al preparar consulta de mis ordenes: ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al obtener las ordenes'
            ], 500);
            return;
        }

        mysqli_stmt_bind_param($stmt, 'i', $usuario['id_usuario']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $ordenes = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        // Agregar el total a cada orden
        foreach ($ordenes as &$orden) 
        {
            $detalles = obtener_detalles_orden($conn, $orden['id_orden']);
            $orden['detalles'] = $detalles;
            $orden['total'] = calcular_total_orden($detalles);
        }

        send_json([
            'ok' => true,
            'data' => $ordenes
        ], 200);
    }

    /**
     * Funcion para actualizar el estado de una orden (de pendiente a pagada o enviada)
     * Esto solo para admins :)
     * @param mysqli La conexion a la base de datos
     * @param int El ID de la orden
     */
    function actualizar_estado_orden($conn, $id)
    {
        if (!$id) 
        {
            send_json([
                'ok' => false,
                'message' => 'Se requiere el ID de la orden'
            ], 400);
            return;
        }

        $usuario = obtener_usuario_actual($conn);

        if (!$usuario) 
        {
            send_json([
                'ok' => false,
                'message' => 'No autorizado'
            ], 401);
            return;
        }

        // Solo admins pueden actualizar el estado
        if ($usuario['rol'] !== 'admin')
        {
            send_json([
                'ok' => false,
                'message' => 'No tienes permisos para actualizar ordenes'
            ], 403);
            return;
        }

        $inputs = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) 
        {
            send_json([
                'ok' => false,
                'message' => 'JSON invalido'
            ], 400);
            return;
        }

        $estado = $inputs['estado'] ?? '';

        if (!in_array($estado, ['pendiente', 'pagada', 'enviada'])) 
        {
            send_json([
                'ok' => false,
                'message' => 'Estado no valido. Opciones: pendiente, pagada o enviada'
            ], 422);
            return;
        }

        // Verificar que la orden existe
        $query = "SELECT id_orden, estado 
                    FROM ordenes 
                    WHERE id_orden = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $orden = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$orden) 
        {
            send_json([
                'ok' => false,
                'message' => 'Orden no encontrada'
            ], 404);
            return;
        }

        // Actualizar el estado
        $query_update = "UPDATE ordenes 
                            SET estado = ? 
                            WHERE id_orden = ?";
        $stmt = mysqli_prepare($conn, $query_update);

        if (!$stmt) 
        {
            error_log('Error al preparar consulta de actualizacion: ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al actualizar la orden'
            ], 500);
            return;
        }

        mysqli_stmt_bind_param($stmt, 'si', $estado, $id);
        
        if (!mysqli_stmt_execute($stmt))
        {
            mysqli_stmt_close($stmt);
            send_json([
                'ok' => false,
                'message' => 'Error al actualizar el estado'
            ], 500);
            return;
        }

        mysqli_stmt_close($stmt);

        send_json([
            'ok' => true,
            'message' => 'Estado actualizado exitosamente',
            'data' => [
                'id_orden' => $id,
                // Para q se vea el cambio
                'estado_anterior' => $orden['estado'],  
                'estado_nuevo' => $estado
            ]
        ], 200);
    }

    /**
     * Funcion para cancelar una orden
     * Los usuarios pueden cancelar su propia orden si esta pendiente
     * Los admins pueden cancelar cualquier orden
     * @param mysqli La conexion a la base de datos 
     * @param int El ID de la orden
     */
    function cancelar_orden($conn, $id)
    {
        if (!$id)
        {
            send_json([
                'ok' => false,
                'message' => 'Se requiere el ID de la orden'
            ], 400);
            return;
        }

        $usuario = obtener_usuario_actual($conn);

        if (!$usuario)
        {
            send_json([
                'ok' => false,
                'message' => 'No autorizado'
            ], 401);
            return;
        }

        // Verificar que la orden existe
        $query_check = "SELECT id_orden, id_usuario, estado 
                        FROM ordenes 
                        WHERE id_orden = ?";
        $stmt = mysqli_prepare($conn, $query_check);
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $orden = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$orden)
        {
            send_json([
                'ok' => false,
                'message' => 'Orden no encontrada'
            ], 404);
            return;
        }

        // Verificar permisos, solo admins o el dueño de la orden
        if ($usuario['rol'] !== 'admin' && $orden['id_usuario'] !== $usuario['id_usuario'])
        {
            send_json([
                'ok' => false,
                'message' => 'No tienes permisos para cancelar esta orden'
            ], 403);
            return;
        }

        // Solo se pueden cancelar ordenes pendientes, excepto si es un admin
        if ($orden['estado'] !== 'pendiente' && $usuario['rol'] !== 'admin')
        {
            send_json([
                'ok' => false,
                'message' => 'Solo se pueden cancelar ordenes pendientes'
            ], 400);
            return;
        }

        // Por si las moscas
        if ($orden['estado'] === 'cancelada') 
        {
            send_json([
                'ok' => false,
                'message' => 'La orden ya esta cancelada'
            ], 400);
            return;
        }

        // Hacer checkpoint para revertir en caso de error
        mysqli_begin_transaction($conn);

        try 
        {
            // Obtener los detalles para restaurar el stock
            $detalles = obtener_detalles_orden($conn, $id);

            // Restaurar el stock de cada producto
            foreach ($detalles as $detalle)
            {
                $query_stock = "UPDATE productos 
                                    SET stock = stock + ? 
                                    WHERE id_producto = ?";
                $stmt_stock = mysqli_prepare($conn, $query_stock);
                mysqli_stmt_bind_param($stmt_stock, 'ii', $detalle['cantidad'], $detalle['id_producto']);
                
                if (!mysqli_stmt_execute($stmt_stock))
                {
                    throw new Exception('Error al restaurar stock: ' . mysqli_error($conn));
                }
                mysqli_stmt_close($stmt_stock);
            }

            // Actualizar el estado a cancelada
            $query_cancel = "UPDATE ordenes 
                                SET estado = 'cancelada' 
                                WHERE id_orden = ?";
            $stmt = mysqli_prepare($conn, $query_cancel);
            mysqli_stmt_bind_param($stmt, 'i', $id);
            
            if (!mysqli_stmt_execute($stmt)) 
            {
                throw new Exception('Error al cancelar la orden: ' . mysqli_error($conn));
            }

            mysqli_stmt_close($stmt);
            // Hacer el commit en la DB para guardar los cambios
            mysqli_commit($conn);

            send_json([
                'ok' => true,
                'message' => 'Orden cancelada exitosamente',
                'data' => [
                    'id_orden' => $id,
                ]
            ], 200);

        } 
        catch (Exception $e) 
        {
            // Revertir los cambios en caso de error
            mysqli_rollback($conn);
            error_log($e->getMessage());
            send_json([
                'ok' => false,
                'message' => 'Error al cancelar la orden'
            ], 500);
        }
    }

    /**
     * Funcion para obtener el total de ventas
     * Esto solo para admins jujuy
     * @param mysqli La conexion a la base de datos
     */
    function obtener_total_ventas($conn)
    {
        $usuario = obtener_usuario_actual($conn);

        if (!$usuario) 
        {
            send_json([
                'ok' => false,
                'message' => 'No autorizado'
            ], 401);
            return;
        }

        // Solo admins pueden ver el total de ventas
        if ($usuario['rol'] !== 'admin') 
        {
            send_json([
                'ok' => false,
                'message' => 'No tienes permiso para ver el total de ventas rufian!!!'
            ], 403);
            return;
        }

        // Obtener el total de ventas (solo ordenes pagadas o enviadas)
        $query = "SELECT COUNT(DISTINCT o.id_orden) as total_ordenes,
                         SUM(d.subtotal) as total_ventas
                  FROM ordenes o
                  INNER JOIN detalles_orden d 
                    ON o.id_orden = d.id_orden
                  WHERE o.estado IN ('pagada', 'enviada')";

        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) 
        {
            error_log('Error al preparar consulta de total de ventas: ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al obtener el total de ventas'
            ], 500);
            return;
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        send_json([
            'ok' => true,
            'data' => [
                'total_ordenes' => intval($data['total_ordenes']),
                'total_ventas' => number_format(floatval($data['total_ventas']), 2)
            ]
        ], 200);
    }

    // HELPERS :)

    /**
     * Funcion para obtener los detalles de una orden
     * @param mysqli La conexion a la base de datos
     * @param int El ID de la orden
     * @return array Los detalles de la orden
     */
    function obtener_detalles_orden($conn, $id_orden)
    {
        $query = "SELECT d.id_detalle, d.id_producto, d.cantidad, d.precio_unitario, d.subtotal,
                         p.nombre, p.imagen
                  FROM detalles_orden d
                  INNER JOIN productos p 
                    ON d.id_producto = p.id_producto
                  WHERE d.id_orden = ?";
        
        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) 
        {
            return [];
        }

        mysqli_stmt_bind_param($stmt, 'i', $id_orden);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $detalles = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        return $detalles;
    }

    /**
     * Funcion para calcular el total de una orden
     * @param array Los detalles de la orden
     * @return float El total de la orden
     */
    function calcular_total_orden($detalles)
    {
        $total = 0;

        foreach ($detalles as $detalle) 
        {
            $total += floatval($detalle['subtotal']);
        }

        // Pa q se vea bonis 
        return number_format($total, 2);
    }

    /**
     * Funcion para obtener el usuario actual basado en el token de autenticacion
     * @param mysqli La conexion a la base de datos
     */
    function obtener_usuario_actual($conn)
    {
        // Obtener el token 
        $token = $_COOKIE['token'] ?? null;

        if (!$token) 
        {
            send_json([
                'ok' => false,
                'message' => 'No hay token'
            ], 401);
            return;
        }

        // Buscar la sesion en la base de datos
        $query = "SELECT u.id_usuario, u.nombre, u.apellido, u.email, u.rol 
                  FROM sessions s 
                  JOIN usuarios u ON s.id_usuario = u.id_usuario 
                  WHERE s.token = ? AND s.expires_at > NOW()";

        $stmt = mysqli_prepare($conn, $query);

        if (!$stmt) 
        {
            error_log('Error al preparar consulta de usuario actual: ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al obtener el usuario'
            ], 500);
            return;
        }

        // Ejecutar el query
        mysqli_stmt_bind_param($stmt, 's', $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $usuario = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Verificar si la sesion es valida
        if (!$usuario) 
        {
            send_json([
                'ok' => false,
                'message' => 'Token invalido'
            ], 401);
            return;
        }

        // Devolver los datos del usuario
        return $usuario;
    }

?>