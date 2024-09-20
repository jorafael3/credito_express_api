<?php
include 'funciones.php';

if (
    isset($_GET["cedula"]) && isset($_GET["numero"])
    && isset($_GET["fecha"])
    && isset($_GET["ingresos"])
    && isset($_GET["instr"])
    && isset($_GET["tipo"])
) {
    $CEDULA = trim($_GET["cedula"]);
    $NUMERO = trim($_GET["numero"]);
    $FECHA = trim($_GET["fecha"]);
    $INGRESOS = trim($_GET["ingresos"]);
    $instr = trim($_GET["instr"]);
    $tipo = trim($_GET["tipo"]);


    // $key = trim($_GET["key"]);
    $KEY = "7uXvhfOAUNbmfiKnzVlSq4uJRj0tx5G2";
    if ($KEY == $KEY) {
        if ($CEDULA != null || $CEDULA != "" || $NUMERO != null || $NUMERO != "") {

            $longitud = strlen($CEDULA);
            $longitud_telefono = strlen($NUMERO);

            // echo "La longitud del string es: " . $longitud;
            if ($longitud == 9) {
                $CEDULA = "0" . $CEDULA;
            }

            // if ($longitud == 9) {
            //     $NUMERO = "0" . $NUMERO;
            // }
            date_default_timezone_set('America/Guayaquil');

            $ID_UNICO = $CEDULA . "_" . date('YmdHis');

            $CEN = encryptCedula($CEDULA);
            if ($CEN[0] == 1) {

                // $DATOS_CREDITO = Obtener_Datos_Credito($CEN[1], $FECHA, $NUMERO, '500');

                $DATOS_CREDITO  = Consulta_api($CEN[1], $NUMERO, $FECHA, $INGRESOS, $instr, $tipo);
                $GUARDAR = Guardar_Datos($CEDULA, $NUMERO, [], $ID_UNICO, "SOLIDARIO");


                echo json_encode($DATOS_CREDITO[1]);
                exit();
            } else {
                $res = array(
                    "SUCCESS" => "0",
                    "MENSAJE" => "ERROR AL ENCRIPTAR CEDULA"
                );

                echo json_encode($res);
                exit();
            }
        } else {
            $res = array(
                "SUCCESS" => "0",
                "MENSAJE" => "CEDULA NO VALIDA"
            );

            echo json_encode($res);
            exit();
        }
    } else {
        $res = array(
            "SUCCESS" => "0",
            "MENSAJE" => "LOS PARAMETROS NO SON VALIDOS"
        );
        echo json_encode($res);
        exit();
    }
} else {
    $res = array(
        "SUCCESS" => "0",
        "MENSAJE" => "URL NO VALIDA, FALTAN PARAMETROS"
    );

    echo json_encode($res);
    exit();
}




function encrypt($cedula)
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

function Consulta_api($cedula_ECrip, $celular, $fecha, $sueldo, $instruccion, $tipoIdentificacion)
{
    ini_set('max_execution_time', '300'); // Tiempo en segundos
    // echo json_encode("aquiaa");
    // exit();
    try {
        $fecha_formateada = $fecha;
        $ingresos = $sueldo;
        $Instruccion = $instruccion;
        $CELULAR = $celular;
        $TipoIdentificacion = $tipoIdentificacion;

        $SEC = Get_Secuencial_Api_Banco();
        $SEC = $SEC[0]["valor"];
        $SEC = intval($SEC) + 1;

        Update_Secuencial_Api_Banco($SEC);
        // echo json_encode($SEC);
        // exit();

        $data = array(
            "transaccion" => 4001,
            "idSession" => "1",
            "secuencial" => $SEC,
            "mensaje" => array(
                "IdCasaComercialProducto" => 8,
                "TipoIdentificacion" =>  strval($TipoIdentificacion),
                "IdentificacionCliente" => $cedula_ECrip, // Encriptar la cédula
                "FechaNacimiento" => strval($fecha_formateada),
                "ValorIngreso" => strval($ingresos),
                "Instruccion" =>  strval($Instruccion),
                "Celular" =>  strval($CELULAR)
            )
        );
        $data_string = json_encode($data);
        $url = 'https://bs-autentica.com/cco/apiofertaccoqa1/api/CasasComerciales/GenerarCalificacionEnPuntaCasasComerciales';
        //$api_key = '0G4uZTt8yVlhd33qfCn5sazR5rDgolqH64kUYiVM5rcuQbOFhQEADhMRHqumswphGtHt1yhptsg0zyxWibbYmjJOOTstDwBfPjkeuh6RITv32fnY8UxhU9j5tiXFrgVz';

        // $url = 'https://bs-autentica.com/cco/ApiOfertaCCO';
        $url = 'https://bs-autentica.com/cco/apiofertacco/api/CasasComerciales/GenerarCalificacionEnPuntaCasasComerciales';
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Máximo tiempo de espera total de 10 segundos
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 180); // Máximo tiempo de espera para conectar de 5 segundos

        $response = (curl_exec($ch));
        $error = (curl_error($ch));
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($response === false) {
            return [0, 'cURL Error: ' . $error];
        }

        if ($http_code !== 200) {
            $a = array(
                "HTTP Error: $http_code",
                "data" => $data,
                "error" => $error,

            );
            return [0, $a];
        }
        $response_array = json_decode($response, true);

        // Verificar si hay un error en la respuesta
        if (isset($response_array['esError'])) {
            // $GUARDAR = Guardar_Datos_Banco($response_array, $ID_UNICO);
            if ($response_array['esError'] == false) {
                if (isset($response_array['mensaje'])) {
                    $response_array['montoMaximo'] = $response_array['mensaje']["montoMaximo"];
                    $response_array['plazoMaximo'] = $response_array['mensaje']["plazoMaximo"];
                    //$response_array['datos'] = $data;
                }
            }
            return [1, $response_array];
        } else {
            $a = array(
                "response" => $response_array,
                "data" => $data,
                "error" => $error,

            );
            // $INC = $this->INCIDENCIAS($_inci);
            return [0, $a, $data, $error, extension_loaded('curl')];
        }
    } catch (Exception $e) {
        // Captura la excepción y maneja el error
        // echo "Error: " . $e->getMessage();
        $param = array(
            "ERROR_TYPE" => "API_SOL_FUNCTION",
            "ERROR_CODE" => "",
            "ERROR_TEXT" => $e->getMessage(),
        );
        return [0, $param];
    }
}

// function Get_Secuencial_Api_Banco()
// {
//     require('conexion.php');

//     try {
//         // sleep(4);
//         // $cedula = trim($param["cedula"]);
//         $arr = "";
//         $query = $pdo->prepare("SELECT * FROM parametros where id = 1");
//         // $query->bindParam(":cedula", $cedula, PDO::PARAM_STR);
//         if ($query->execute()) {
//             $result = $query->fetchAll(PDO::FETCH_ASSOC);
//             return $result;
//         }
//     } catch (PDOException $e) {
//         $e = $e->getMessage();
//         return [0, "INTENTE DE NUEVO"];
//     }
// }

// function Update_Secuencial_Api_Banco($SEC)
// {
//     require('conexion.php');

//     try {
//         // sleep(4);
//         // $cedula = trim($param["cedula"]);
//         $arr = "";
//         $query = $pdo->prepare("UPDATE parametros 
//             SET valor = :valor
//         where id = 1");
//         $query->bindParam(":valor", $SEC, PDO::PARAM_STR);
//         if ($query->execute()) {
//             $result = $query->fetchAll(PDO::FETCH_ASSOC);
//             return $result;
//         }
//     } catch (PDOException $e) {
//         $e = $e->getMessage();
//         return [0, "INTENTE DE NUEVO"];
//     }
// }
