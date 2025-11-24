<?php
    require_once __DIR__ . '/_headers.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../helpers/send_json.php';

    // Obtener el metodo HTTP
    $method = $_SERVER['REQUEST_METHOD'];
    // Obtener la accion solicitada
    $action = $_GET['action'] ?? '';

    $conn = get_db_connection();

    switch ($action) {
        case 'register':
            if ($method !== 'POST') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            registrar_usuario($conn);
            break;
        
        case 'login':
            if ($method !== 'POST') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            // iniciar_sesion($conn);
            break;
        
        case 'logout':
            if ($method !== 'POST') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            // cerrar_sesion();
            break;
        
        case 'me':
            if ($method !== 'GET') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            // obtener_usuario_actual($conn);
            break;

        default:
            send_json([
                'ok' => false,
                'message' => 'Accion no valida'
            ], 400);
            break;
    }

    /**
     * Funcion para registrar un nuevo usuario
     * @param mysqli La conexion a la base de datos
     */
    function registrar_usuario($conn)
    {
        // Obtener el input
        $inputs = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) 
        {
            send_json([
                'ok' => false,
                'message' => 'JSON invalido'
            ], 400);
        }

        $nombre = trim($inputs['nombre'] ?? '');
        $apellido = trim($inputs['apellido'] ?? '');
        $email = trim($inputs['email'] ?? '');
        $password = $inputs['password'] ?? '';
        $rol = trim($inputs['rol'] ?? 'cliente');

        // Validar los inputs
        if (!$nombre || !$email || !$password) 
        {
            send_json([
                'ok' => false,
                'message' => 'Nombre, email y contraseña son obligatorios'
            ], 422);
        }

        // Validar el rol
        if (!in_array($rol, ['admin', 'cliente'])) 
        {
            $rol = 'cliente';
        }

        // Validar el formato de email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        {
            send_json([
                'ok' => false,
                'message' => 'Email no valido'
            ], 422);
        }

        // Validar la longitud de la contrasenia
        if (strlen($password) < 6) 
        {
            send_json([
                'ok' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ], 422);
        }

        // Verificar si el email ya existe
        $query_check = "SELECT id_usuario FROM usuarios WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query_check);
        
        if (!$stmt) 
        {
            error_log('Error al preparar consulta de verificacion: ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al verificar al usuario'
            ], 500);
        }

        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        // Si el query devuelve una fila significa que el email ya esta registrado
        if (mysqli_fetch_assoc($result)) 
        {
            mysqli_stmt_close($stmt);
            send_json([
                'ok' => false,
                'message' => 'El email ya esta registrado'
            ], 409);
        }
        
        mysqli_stmt_close($stmt);

        // Hashear la contrasenia
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Insertar al usuario
        $query_insert = "INSERT INTO usuarios (nombre, apellido, email, password_hash, rol) 
                        VALUES (?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query_insert);
        
        if (!$stmt) 
        {
            error_log('Error al preparar la consulta para registrar un usuario: ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al crear al usuario'
            ], 500);
        }

        mysqli_stmt_bind_param($stmt, 'sssss', $nombre, $apellido, $email, $password_hash, $rol);
        
        if (!mysqli_stmt_execute($stmt)) 
        {
            error_log('Error al insertar a un usuario: ' . mysqli_error($conn));
            mysqli_stmt_close($stmt);
            send_json([
                'ok' => false,
                'message' => 'Error al crear a un usuario'
            ], 500);
        }

        mysqli_stmt_close($stmt);

        send_json([
            'ok' => true,
            'message' => 'Usuario registrado exitosamente'
        ], 201);
    }

?>
