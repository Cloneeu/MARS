<?php
    /**
     * Esta funcion regresa un JSON con la informacion que se pase 
     * como argumento y su respectivo codigo de status 
     * 
     * @param data La infomacion para enviar
     * @param status_code El codigo a enviar
     * 
     * Nota: Esta funcion solo la hice por que me da mucha flojera
     * poner tanto echo json_encode :(
    */
    function send_json($data, $status_code = 200) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status_code);
        // Usar JSON_UNESCAPED_UNICODE para soportar caracteres especiales
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
?>