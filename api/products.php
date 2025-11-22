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
            crear_producto($conn);
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
        // Checar si se proporciono un LIMIT (si no el valor default es devolver todos los productos) :P
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : PHP_INT_MAX;
        
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

        // Unir el parametro limit y offset a la consulta
        mysqli_stmt_bind_param($stmt, 'ii', $limit, $offset);

        // Ejecutar la consulta y checar q si jale
        if (!mysqli_stmt_execute($stmt))
        {
            send_json([
                'ok' => false,
                'message' => 'No se pudieron recuperar los productos'
            ], 500);
            return;
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

    /**
     * Funcion para crear un nuevo producto en la base de datos
     * @param mysqli Una conexion a la base de datos 
     */
    function crear_producto($conn)
    {
        // Obtener y validar los datos del producto
        $nombre = trim($_POST['nombre'] ?? '');
        $precio = (float) ($_POST['precio'] ?? 0);
        $descripcion = trim($_POST['descripcion'] ?? '');
        $stock = (int) ($_POST['stock'] ?? 0);

        if (!$nombre || $precio <= 0 || $stock <= 0) {
            send_json([
                'ok' => false,
                'message' => 'El nombre, precio y stock son obligatorios'
            ], 422);
            return;
        }

        // Solo para saber donde guardar la imagen
        $config = require __DIR__ . '/../config/config.php';

        $imagen_path = null;
        if (!empty($_FILES['imagen']['name'])) 
        {
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp'];

            if (!in_array($ext, $allowed)) 
            {
                send_json([
                    'ok'=>false, 
                    'message'=>'Formato de imagen no permitido'
                ], 415);
                return;
            }

            // Nombre del archivo que se va a guardar
            $fname = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            // Path donde se va a guardar la imagen
            $dest = realpath($config['upload_dir']) . DIRECTORY_SEPARATOR . $fname;

            // Mover el archivo subido a la carpeta de uploads
            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $dest))
            {
                send_json([
                    'ok'=>false, 
                    'message'=>'Error al subir la imagen'
                ], 500);
                return;
            }
            $imagen_path = $fname;
        }        

        // Insertar el nuevo producto a la base de datos
        $query = "INSERT INTO productos (nombre, descripcion, precio, imagen, stock) VALUES(?, ?, ?, ?, ?)";

        // Statement preparado para evitar SQL Injection
        $stmt = mysqli_prepare($conn, $query);

        // Por si falla la preparacion de la consulta :(
        if (!$stmt) {
            // Para ver el error completo en los logs
            error_log('Error al preparar la consulta: ' . $query . ' @@@ ' . mysqli_error($conn));

            // Enviar un mensaje genericon al cliente 
            send_json([
                'ok' => false,
                'message' => 'Error al insertar el producto'
            ], 500);
            return;
        }

        // Unir los parametros a la consulta
        mysqli_stmt_bind_param($stmt, 'ssdsi', $nombre, $descripcion, $precio, $imagen_path, $stock);

        // Ejecutar la consulta y checar q si jale
        if (!mysqli_stmt_execute($stmt))
        {
            send_json([
                'ok' => false,
                'message' => 'No se pudo insertar el producto'
            ], 500);
            return;
        }

        mysqli_stmt_close($stmt);

        // Mandar mensaje de que todo salio bien
        send_json([
            'ok' => true,
            'message' => 'Producto creado exitosamente'
        ], 201);
    }

?>