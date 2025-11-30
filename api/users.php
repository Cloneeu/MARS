<?php
    require_once __DIR__ . '/_headers.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../helpers/send_json.php';

    // Obtener el metodo HTTP
    $method = $_SERVER['REQUEST_METHOD'];
    // Obtener la accion solicitada
    $action = $_GET['action'] ?? '';

    $conn = get_db_connection();

    switch ($action) 
    {
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
            iniciar_sesion($conn);
            break;
        
        case 'logout':
            if ($method !== 'POST') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            cerrar_sesion($conn);
            break;
        
        case 'me':
            if ($method !== 'GET') 
            {
                send_json(['ok' => false, 'message' => 'Metodo no permitido'], 405);
                break;
            }
            obtener_usuario_actual($conn);
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
            return;
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
            return;
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
            return;
        }

        // Validar la longitud de la contrasenia
        if (strlen($password) < 6) 
        {
            send_json([
                'ok' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres'
            ], 422);
            return;
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
            return;
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
            return;
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
            return;
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
            return;
        }

        mysqli_stmt_close($stmt);

        send_json([
            'ok' => true,
            'message' => 'Usuario registrado exitosamente'
        ], 201);
    }

    /**
     * Funcion para iniciar sesion
     * @param mysqli La conexion a la base de datos
     */
    function iniciar_sesion($conn)
    {
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

        $email = trim($inputs['email'] ?? '');
        $password = $inputs['password'] ?? '';

        // Validar los inputs
        if (!$email || !$password) 
        {
            send_json([
                'ok' => false,
                'message' => 'Email y contraseña son obligatorios'
            ], 422);
            return;
        }

        // Buscar al usuario por email
        $query = "SELECT id_usuario, nombre, apellido, email, password_hash, rol FROM usuarios WHERE email = ?";
        $stmt = mysqli_prepare($conn, $query);
        
        if (!$stmt) 
        {
            error_log('Error al preparar consulta de login: ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al iniciar sesion'
            ], 500);
            return;
        }

        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $usuario = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        // Verificar si el usuario existe
        if (!$usuario) 
        {
            send_json([
                'ok' => false,
                'message' => 'Usuario no encontrado'
            ], 401);
            return;
        }

        // Verificar la contrasenia
        if (!password_verify($password, $usuario['password_hash'])) 
        {
            send_json([
                'ok' => false,
                'message' => 'Credenciales invalidas'
            ], 401);
            return;
        }

        // Generar un token 
        $token = bin2hex(random_bytes(32));
        
        // Definir la fecha de expiracion (2 horas)
        $expires_at = date('Y-m-d H:i:s', strtotime('+2 hours'));

        // Insertar la sesion en la base de datos
        $query_session = "INSERT INTO sessions (id_usuario, token, expires_at) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query_session);
        
        if (!$stmt) 
        {
            error_log('Error al preparar consulta de sesion: ' . mysqli_error($conn));
            send_json([
                'ok' => false,
                'message' => 'Error al iniciar la sesion'
            ], 500);
            return;
        }

        mysqli_stmt_bind_param($stmt, 'iss', $usuario['id_usuario'], $token, $expires_at);
        
        if (!mysqli_stmt_execute($stmt)) 
        {
            error_log('Error al insertar sesion: ' . mysqli_error($conn));
            mysqli_stmt_close($stmt);
            send_json([
                'ok' => false,
                'message' => 'Error al iniciar la sesion'
            ], 500);
            return;
        }

        mysqli_stmt_close($stmt);

        setcookie('token', $token, [
            'expires' => time() + 7200,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        ]);

        // Devolver el token y datos del usuario
        send_json([
            'ok' => true,
            'message' => 'Sesion iniciada exitosamente',
            'usuario' => [
                'id_usuario' => $usuario['id_usuario'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'email' => $usuario['email'],
                'rol' => $usuario['rol']
            ]
        ], 200);
    }

    /**
     * Funcion para cerrar sesion
     * @param mysqli La conexion a la base de datos
     */
    function cerrar_sesion($conn)
    {
        // obtener el token 
        $token = $_COOKIE['token'] ?? null;

        if ($token)
        {
            // Eliminar la sesion de la base de datos
            $query = "DELETE FROM sessions WHERE token = ?";
            $stmt = mysqli_prepare($conn, $query);

            if ($stmt) 
            {
                // Ejecutar el query
                mysqli_stmt_bind_param($stmt, 's', $token);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            else 
            {
                error_log('Error al preparar consulta de cierre de sesion: ' . mysqli_error($conn));
                send_json([
                    'ok' => false,
                    'message' => 'Error al cerrar la sesion'
                ], 500);
                return;
            }
            
            // Eliminar la cookie del token
            setcookie('token', '', [
                'expires' => time() - 3600, // Si se pasa una fecha en el pasado, la cookie se elimina en automatico
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            ]);

            send_json([
                'ok' => true,
                'message' => 'Sesion cerrada'
            ], 200);
        }
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
        send_json([
            'ok' => true,
            'usuario' => [
                'id_usuario' => $usuario['id_usuario'],
                'nombre' => $usuario['nombre'],
                'apellido' => $usuario['apellido'],
                'email' => $usuario['email'],
                'rol' => $usuario['rol']
            ]
        ], 200);
    }
