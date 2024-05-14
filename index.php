<?php

function encryptCedula($cedula)
{
    // Contenido de la clave pública
    $public_key_file = "PBKey.txt";
    // Lee el contenido del archivo PEM
    $public_key_content = file_get_contents($public_key_file);
    // Elimina espacios en blanco adicionales alrededor del contenido
    $public_key_content = trim($public_key_content);

    $rsaKey = openssl_pkey_get_public($public_key_content);
    if (!$rsaKey) {
        // Manejar el error de obtener la clave pública
        return [0, openssl_error_string(), $public_key_file];
    }
    // // Divide el texto en bloques para encriptar
    $encryptedData = '';
    $encryptionSuccess = openssl_public_encrypt($cedula, $encryptedData, $rsaKey);

    // Obtener detalles del error, si hubo alguno
    // $error = openssl_error_string();
    // if ($error) {
    //     // Manejar el error de OpenSSL
    //     return $error;
    // }

    // Liberar la clave pública RSA de la memoria
    openssl_free_key($rsaKey);

    if ($encryptionSuccess === false) {
        // Manejar el error de encriptación
        return [0, null, $public_key_file];
    }

    // Devolver la cédula encriptada
    return [1, base64_encode($encryptedData)];
    // echo json_encode(base64_encode($encryptedData));
    // exit();
    // return ($encrypted);
}


function Obtener_Datos_Credito($cedula, $fecha, $celular, $ID_UNICO)
{
    try {

        $fecha_formateada = $fecha;
        $ingresos = "500";
        $Instruccion = "SECU";
        $CELULAR = $celular;


        // $SEC = Get_Secuencial_Api_Banco();
        $SEC = intval(300) + 1;
        // Update_Secuencial_Api_Banco($SEC);

        $cedula_ECrip = encryptCedula($cedula);
        if ($cedula_ECrip[0] == 0) {
            return [0, $cedula_ECrip, [], []];
        } else {
            $cedula_ECrip = $cedula_ECrip[1];
        }

        $data = array(
            "transaccion" => 4001,
            "idSession" => "1",
            "secuencial" => $SEC,
            "mensaje" => array(
                "IdCasaComercialProducto" => 8,
                "TipoIdentificacion" => "CED",
                "IdentificacionCliente" => $cedula_ECrip, // Encriptar la cédula
                "FechaNacimiento" => $fecha_formateada,
                "ValorIngreso" => $ingresos,
                "Instruccion" =>  $Instruccion,
                "Celular" =>  $CELULAR
            )
        );
        $data_string = json_encode($data);
        $url = 'https://bs-autentica.com/cco/apiofertaccoqa1/api/CasasComerciales/GenerarCalificacionEnPuntaCasasComerciales';
        $api_key = '0G4uZTt8yVlhd33qfCn5sazR5rDgolqH64kUYiVM5rcuQbOFhQEADhMRHqumswphGtHt1yhptsg0zyxWibbYmjJOOTstDwBfPjkeuh6RITv32fnY8UxhU9j5tiXFrgVz';
        $ch = curl_init($url);
        // curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        // $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string),
            'ApiKeySuscripcion: ' . $api_key
        ));
        $response = (curl_exec($ch));
        $error = (curl_error($ch));
        curl_close($ch);
        // rewind($verbose);
        // $verboseLog = stream_get_contents($verbose);
        $response_array = json_decode($response, true);

        var_dump($response_array);
        var_dump($error);
        // var_dump($verboseLog);

        // if (extension_loaded('curl')) {
        //     echo "cURL está habilitado en este servidor.";
        // } else {
        //     echo "cURL no está habilitado en este servidor.";
        // }

        // Verificar si hay un error en la respuesta
        // if (isset($response_array['esError'])) {

        //     $GUARDAR = Guardar_Datos_Banco($response_array, $ID_UNICO);
        //     return [1, $response_array, $GUARDAR];
        // } else {
        //     // $INC = $this->INCIDENCIAS($_inci);
        //     return [0, $response_array, $data, $error, $verboseLog, extension_loaded('curl')];
        // }
    } catch (Exception $e) {
        // Captura la excepción y maneja el error
        // echo "Error: " . $e->getMessage();
        $param = array(
            "ERROR_TYPE" => "API_SOL_FUNCTION",
            "ERROR_CODE" => "",
            "ERROR_TEXT" => $e->getMessage(),
        );
        var_dump($param);
    }
}

// var_dump(encryptCedula("0931531115"));

Obtener_Datos_Credito("0931531115", "19940412", "0969786231", "");

