<?php

require('conexion.php');

// ejemplo.php
if (isset($argv[1])) {
    $cedula = $argv[1];
    $fecha = $argv[2];
    $celular = $argv[3];
    $ID_UNICO = $argv[4];

    // $b = encryptCedula($cedula);
    $b = Obtener_Datos_Credito($cedula, $fecha, $celular, $ID_UNICO);
    echo json_encode($b);
} else {
    echo "No se recibió ningún parámetro.";
}


function encryptCedula($cedula)
{
    // Contenido de la clave pública
    $public_key_file = "C:/xampp/htdocs/credito_express_api/PBKey.txt";
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


        $SEC = Get_Secuencial_Api_Banco();
        $SEC = intval($SEC[0]["valor"]) + 1;
        Update_Secuencial_Api_Banco($SEC);

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

        // echo json_encode($data);
        // exit();
        // Convertir datos a JSON
        $data_string = json_encode($data);
        // URL del API
        $url = 'https://bs-autentica.com/cco/apiofertaccoqa1/api/CasasComerciales/GenerarCalificacionEnPuntaCasasComerciales';
        // API Key
        $api_key = '0G4uZTt8yVlhd33qfCn5sazR5rDgolqH64kUYiVM5rcuQbOFhQEADhMRHqumswphGtHt1yhptsg0zyxWibbYmjJOOTstDwBfPjkeuh6RITv32fnY8UxhU9j5tiXFrgVz';
        // Inicializa la sesión cURL
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        // Configura las opciones de la solicitud
        $verbose = fopen('php://temp', 'w+');
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
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.7; rv:7.0.1) Gecko/20100101 Firefox/7.0.1');

        // Ejecuta la solicitud y obtiene la respuesta
        $response = (curl_exec($ch));
        // Cierra la sesión cURL
        $error = (curl_error($ch));
        curl_close($ch);
        // Imprime la respuesta
        // echo $response;
        // return [1, $ARRAY];
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        $response_array = json_decode($response, true);



        // if (extension_loaded('curl')) {
        //     echo "cURL está habilitado en este servidor.";
        // } else {
        //     echo "cURL no está habilitado en este servidor.";
        // }

        // Verificar si hay un error en la respuesta
        if (isset($response_array['esError'])) {

            $GUARDAR = Guardar_Datos_Banco($response_array, $ID_UNICO);
            return [1, $response_array, $GUARDAR];
        } else {
            // $INC = $this->INCIDENCIAS($_inci);
            return [0, $response_array, $data, $error, $verboseLog, extension_loaded('curl')];
        }
    } catch (Exception $e) {
        // Captura la excepción y maneja el error
        // echo "Error: " . $e->getMessage();
        $param = array(
            "ERROR_TYPE" => "API_SOL_FUNCTION",
            "ERROR_CODE" => "",
            "ERROR_TEXT" => $e->getMessage(),
        );
        return [0, "Error al procesar la solictud banco", $e->getMessage()];
    }
}


function Get_Secuencial_Api_Banco()
{
    require('conexion.php');

    try {
        // sleep(4);
        // $cedula = trim($param["cedula"]);
        $arr = "";
        $query = $pdo->prepare("SELECT * FROM parametros where id = 1");
        // $query->bindParam(":cedula", $cedula, PDO::PARAM_STR);
        if ($query->execute()) {
            $result = $query->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
    } catch (PDOException $e) {
        $e = $e->getMessage();
        return [0, "INTENTE DE NUEVO"];
    }
}

function Update_Secuencial_Api_Banco($SEC)
{
    require('conexion.php');

    try {
        // sleep(4);
        // $cedula = trim($param["cedula"]);
        $arr = "";
        $query = $pdo->prepare("UPDATE parametros 
            SET valor = :valor
        where id = 1");
        $query->bindParam(":valor", $SEC, PDO::PARAM_STR);
        if ($query->execute()) {
            $result = $query->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
    } catch (PDOException $e) {
        $e = $e->getMessage();
        return [0, "INTENTE DE NUEVO"];
    }
}

function Guardar_Datos_Banco($VAL_CREDITO, $ID_UNICO)
{
    require('conexion.php');

    try {
        date_default_timezone_set('America/Guayaquil');

        $DATOS_CREDITO = $VAL_CREDITO;
        // echo json_encode($DATOS_CREDITO);
        // exit();

        $API_SOL_codigo = $DATOS_CREDITO["codigo"];
        $API_SOL_descripcion = $DATOS_CREDITO["descripcion"];
        $API_SOL_esError = $DATOS_CREDITO["esError"];
        $API_SOL_idSesion = $DATOS_CREDITO["idSesion"];
        $API_SOL_secuencial = $DATOS_CREDITO["secuencial"];
        $API_SOL_ESTADO =  0; // ERROR DESCONOCIDO

        if (isset($DATOS_CREDITO["mensaje"])) {
            $API_SOL_campania = $DATOS_CREDITO["mensaje"]["campania"];
            $API_SOL_identificacion = $DATOS_CREDITO["mensaje"]["identificacion"];
            $API_SOL_lote = $DATOS_CREDITO["mensaje"]["lote"];
            $API_SOL_montoMaximo = $DATOS_CREDITO["mensaje"]["montoMaximo"];
            $API_SOL_nombreCampania = $DATOS_CREDITO["mensaje"]["nombreCampania"];
            $API_SOL_plazoMaximo = $DATOS_CREDITO["mensaje"]["plazoMaximo"];
            $API_SOL_promocion = $DATOS_CREDITO["mensaje"]["promocion"];
            $API_SOL_segmentoRiesgo = $DATOS_CREDITO["mensaje"]["segmentoRiesgo"];
            $API_SOL_subLote = $DATOS_CREDITO["mensaje"]["subLote"];
            $credito_aprobado = floatval($DATOS_CREDITO["mensaje"]["montoMaximo"]) > 0 ? 1 : 0;
            $credito_aprobado_texto = floatval($DATOS_CREDITO["mensaje"]["montoMaximo"]) > 0 ? "APROBADO" : "RECHAZADO";
            $API_SOL_ESTADO =  1;

            $sql = "UPDATE creditos_solicitados
            SET

                API_SOL_codigo = :API_SOL_codigo,
                API_SOL_descripcion =:API_SOL_descripcion,
                API_SOL_eserror = :API_SOL_eserror,
                API_SOL_idSesion =:API_SOL_idSesion,
                API_SOL_secuencial = :API_SOL_secuencial,


                API_SOL_campania =:API_SOL_campania,
                API_SOL_identificacion =:API_SOL_identificacion,
                API_SOL_lote =:API_SOL_lote,
                API_SOL_montoMaximo =:API_SOL_montoMaximo,
                API_SOL_nombreCampania =:API_SOL_nombreCampania,
                API_SOL_plazoMaximo =:API_SOL_plazoMaximo,
                API_SOL_promocion =:API_SOL_promocion,
                API_SOL_segmentoRiesgo =:API_SOL_segmentoRiesgo,
                API_SOL_subLote =:API_SOL_subLote,
                credito_aprobado = :credito_aprobado,
                credito_aprobado_texto = :credito_aprobado_texto,

                API_SOL_ESTADO = :API_SOL_ESTADO,

                EST_REGISTRO = 0
            WHERE ID_UNICO = :ID_UNICO";
        } else {
            $hora_actual = date('G');

            if ($DATOS_CREDITO['descripcion'] == "No tiene oferta") {
                $API_SOL_ESTADO =  2;
            }
            if ($DATOS_CREDITO['descripcion'] == "Ha ocurrido un error" && $hora_actual >= 21) {
                $API_SOL_ESTADO =  3;
            }

            $sql = "UPDATE creditos_solicitados
            SET
                API_SOL_codigo = :API_SOL_codigo,
                API_SOL_descripcion =:API_SOL_descripcion,
                API_SOL_eserror = :API_SOL_eserror,
                API_SOL_idSesion =:API_SOL_idSesion,
                API_SOL_secuencial = :API_SOL_secuencial,
                API_SOL_ESTADO = :API_SOL_ESTADO,

                EST_REGISTRO = 0
            WHERE ID_UNICO = :ID_UNICO";
        }
        $query = $pdo->prepare($sql);
        $query->bindParam(":API_SOL_codigo", $API_SOL_codigo, PDO::PARAM_STR);
        $query->bindParam(":API_SOL_descripcion", $API_SOL_descripcion, PDO::PARAM_STR);
        $query->bindParam(":API_SOL_eserror", $API_SOL_eserror, PDO::PARAM_STR);
        $query->bindParam(":API_SOL_idSesion", $API_SOL_idSesion, PDO::PARAM_STR);
        $query->bindParam(":API_SOL_secuencial", $API_SOL_secuencial, PDO::PARAM_STR);

        $query->bindParam(":API_SOL_ESTADO", $API_SOL_ESTADO, PDO::PARAM_STR);

        if ($API_SOL_esError == false) {
            $query->bindParam(":API_SOL_campania", $API_SOL_campania, PDO::PARAM_STR);
            $query->bindParam(":API_SOL_identificacion", $API_SOL_identificacion, PDO::PARAM_STR);
            $query->bindParam(":API_SOL_lote", $API_SOL_lote, PDO::PARAM_STR);
            $query->bindParam(":API_SOL_montoMaximo", $API_SOL_montoMaximo, PDO::PARAM_STR);
            $query->bindParam(":API_SOL_nombreCampania", $API_SOL_nombreCampania, PDO::PARAM_STR);
            $query->bindParam(":API_SOL_plazoMaximo", $API_SOL_plazoMaximo, PDO::PARAM_STR);
            $query->bindParam(":API_SOL_promocion", $API_SOL_promocion, PDO::PARAM_STR);
            $query->bindParam(":API_SOL_segmentoRiesgo", $API_SOL_segmentoRiesgo, PDO::PARAM_STR);
            $query->bindParam(":API_SOL_subLote", $API_SOL_subLote, PDO::PARAM_STR);
            $query->bindParam(":credito_aprobado", $credito_aprobado, PDO::PARAM_STR);
            $query->bindParam(":credito_aprobado_texto", $credito_aprobado_texto, PDO::PARAM_STR);
        }
        $query->bindParam(":ID_UNICO", $ID_UNICO, PDO::PARAM_STR);

        if ($query->execute()) {
            $result = $query->fetchAll(PDO::FETCH_ASSOC);
            return ([1, "DATOS_API_GUARDARDOS", $ID_UNICO]);
        } else {
            $err = $query->errorInfo();
            return ([0, "ERROR AL GUARDAR", $ID_UNICO, $err]);
        }
    } catch (PDOException $e) {
        $e = $e->getMessage();
        echo json_encode([0, "ERROR AL GUARDAR", $e]);
        exit();
    }
}
