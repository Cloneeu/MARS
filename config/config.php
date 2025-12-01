<?php
    // Arreglo para guardar las variables de entorno
    $env = [];
    if (file_exists(__DIR__ . '/../.env')) 
    {
        // Arreglo para guardar cada línea del .env
        $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) 
        {
            // Limpiar espacios y otros caracteres no deseados, y omitir comentarios
            if (strpos(trim($line), '#') === 0) 
            {
                continue;
            }

            // Ignorar líneas sin "="
            if (strpos($line, '=') === false)
            {
            continue; 
            }

            // Dividir cada linea en clave y valor 
            list($key, $value) = explode('=', $line, 2);
            
            // Por si acaso hay espacios alrededor de la clave o el valor
            $env[trim($key)] = trim($value);
        }
        
    }
$rootDir = realpath(__DIR__ . '/..'); // asegura ruta absoluta

// Carpeta dentro de public
$uploadDir = $rootDir . '/public/uploads';
// Crear carpeta si no existe
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true); // permisos rwxrwxr-x
}
    // Retornar toda la configuracion 
    return [
       'db' => [
        'host' => $env['DB_HOST'] ?? 'localhost',
        'port' => $env['DB_PORT'] ?? 3306,
        'name' => $env['DB_NAME'] ?? '',
        'user' => $env['DB_USER'] ?? '',
        'pass' => $env['DB_PASS'] ?? '',
    ],
        
        'base_url' => $env['BASE_URL'] ?? '',
        'upload_dir' => $uploadDir, // ruta absoluta segura    
    ];
?>