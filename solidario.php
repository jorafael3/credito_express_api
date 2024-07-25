<?php
if (isset($_GET["cedula"])) {
    $CEDULA = trim($_GET["cedula"]);
    if ($CEDULA != null || $CEDULA != "") {

        $longitud = strlen($CEDULA);
        // echo "La longitud del string es: " . $longitud;
        if ($longitud == 9) {
            $CEDULA = "0" . $CEDULA;
        }
        Principal($CEDULA);
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
        "MENSAJE" => "URL NO VALIDA"
    );

    echo json_encode($res);
    exit();
}


function Principal($CEDULA)
{
    $C = Guardar_Cedula($CEDULA);
    $EN = OBTENER_ENCRIPT($CEDULA);
    // echo json_encode($EN);
    // exit();

    if ($EN[0] == 1) {
        $ENCRY = trim($EN[1][0]["cedula_encrypt"]);
        $API = CONSULTA_API_REG_DEMOGRAFICO(trim($ENCRY));
        // echo json_encode($ENCRY);
        // exit();
        if ($API[0] == 1) {
            $API[1]["CREDITO_SOLIDARIO"]  = [];
            $IDENTIFICACION = $API[1]["SOCIODEMOGRAFICO"][0]["IDENTIFICACION"];
            $FECH_NAC = $API[1]["SOCIODEMOGRAFICO"][0]["FECH_NAC"];
            $date = DateTime::createFromFormat('d/m/Y', $FECH_NAC);
            $formattedDate = $date->format('Ymd');
            if (count($API[1]["DEPENDIENTES"]) > 0) {
                $TelefonoCelularAfiliado = $API[1]["DEPENDIENTES"][0]["TelefonoCelularAfiliado"];
                $SueldoPromedio = $API[1]["DEPENDIENTES"][0]["SueldoPromedio"];
            } else {
                $TelefonoCelularAfiliado = $API[1]["INDEPENDIENTES"][0]["TELEFONO"];
                $SueldoPromedio = "500";
            }

            $API_EN = encryptCedula($CEDULA);
            if ($API_EN[0] == 1) {
                $cedula_ECrip = $API_EN[1];
                $API_SOL = Obtener_Datos_Credito($cedula_ECrip, $formattedDate, $TelefonoCelularAfiliado, $SueldoPromedio);
                if ($API_SOL[0] == 1) {
                    $API[1]["CREDITO_SOLIDARIO"] = [$API_SOL[1]];
                    echo json_encode($API[1]);
                    exit();
                } else {
                    $API[1]["CREDITO_SOLIDARIO"] = [$API_SOL[1]];
                    echo json_encode($API[1]);
                    exit();
                }
            } else {
                $API[1]["CREDITO_SOLIDARIO"] = [$API_EN[1]];
                echo json_encode($API[1]);
                exit();
            }
        } else {
            $API[1]["CREDITO_SOLIDARIO"] = [$API[1]];
            echo json_encode($API[1]);
            exit();
        }
    } else {
        echo json_encode($EN);
        exit();
    }
}

function Guardar_Cedula($CEDULA)
{
    require('conexion.php');

    try {
        $arr = "";
        $query = $pdo->prepare("INSERT INTO encript_agua
        (
            cedula
        )values(:cedula)");
        $query->bindParam(":cedula", $CEDULA, PDO::PARAM_STR);
        if ($query->execute()) {
            $result = $query->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
    } catch (PDOException $e) {
        $e = $e->getMessage();
        return [0, "INTENTE DE NUEVO"];
    }
}



function OBTENER_ENCRIPT($CEDULA)
{
    require('conexion.php');

    try {
        set_time_limit(180);
        $start_time = microtime(true);

        while (true) {
            $current_time = microtime(true);
            $elapsed_time = $current_time - $start_time;
            // Verificar si el tiempo transcurrido excede el límite de tiempo máximo permitido (por ejemplo, 120 segundos)
            if (round($elapsed_time, 0) >= 180) {
                $_inci = array(
                    "ERROR_TYPE" => "API SOL 2",
                    "ERROR_CODE" => "API SOL MAX EXCECUTIN TIME",
                    "ERROR_TEXT" => "",
                );
                return [2, "La consulta excedió el tiempo máximo permitido"];
            }
            // echo json_encode("Tiempo transcurrido: " . $elapsed_time . " segundos\n");

            $query = $pdo->prepare("SELECT * from encript_agua
            where encrypt = 1 and cedula = :cedula
            order by fecha 
            limit 1
            ");
            $query->bindParam(":cedula", $CEDULA, PDO::PARAM_STR);
            if ($query->execute()) {
                $result = $query->fetchAll(PDO::FETCH_ASSOC);

                if (count($result) > 0) {
                    $encry = trim($result[0]["encrypt"]);
                    if ($encry == 1) {
                        return [1, $result];
                    } else {
                        continue;
                    }
                }
            } else {
                $err = $query->errorInfo();
                return [0,  $err];
            }
            // return [0, "INTENTE DE NUEVO"];
        }
    } catch (Exception $e) {
        $e = $e->getMessage();
        return [0, "INTENTE DE NUEVO"];
    }
}

function CONSULTA_API_REG_DEMOGRAFICO($cedula_encr)
{
    // $cedula_encr = "yt3TIGS4cvQQt3+q6iQ2InVubHr4hm4V7cxn1V3jFC0=";
    $old_error_reporting = error_reporting();
    // Desactivar los mensajes de advertencia
    error_reporting($old_error_reporting & ~E_WARNING);
    // Realizar la solicitud
    // Restaurar el nivel de informe de errores original

    try {

        $url = "https://consultadatos-dataconsulting.ngrok.app/api/ServicioMFC?clientId=" . trim($cedula_encr);
        // $url = "http://161.97.88.203:7071/api/ServicioMFC?clientId=" . trim($cedula_encr);

        // Datos a enviar en la solicitud POST
        $data = [
            "id" => $cedula_encr,
            "emp" => "SALVACERO",
            "img" => ""
        ];

        // Codificar los datos en formato JSON
        $jsonData = json_encode($data);

        // Inicializar cURL
        $ch = curl_init($url);

        // Configurar opciones de cURL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Recibir la respuesta como una cadena de texto
        curl_setopt($ch, CURLOPT_POST, true); // Enviar una solicitud POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData); // Datos a enviar en la solicitud POST
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData),
            'apiKey: DNkAgQHRnuMIwJFY3pVCrwDtmyuJajmQEMlE' // Agregar la API key en el encabezado
        ]);
        // Ejecutar la solicitud
        $response = curl_exec($ch);
        // Manejar errores
        if (curl_errno($ch)) {
            // echo 'Error:' . curl_error($ch);
            return [0, curl_error($ch)];
        } else {
            $data = json_decode($response, true);
            $data["SOCIODEMOGRAFICO"][0]["CALLENUM"] = $data["SOCIODEMOGRAFICO"][0]["CALLE"] . " NUM " . $data["SOCIODEMOGRAFICO"][0]["NUM"];
            $data["SOCIODEMOGRAFICO"][0]["CALLE_NUM"] = $data["SOCIODEMOGRAFICO"][0]["CALLE"] . " NUM " . $data["SOCIODEMOGRAFICO"][0]["NUM"];
            return [1, $data];
        }
        // Cerrar cURL
        curl_close($ch);
    } catch (Exception $e) {
        $e = $e->getMessage();
        return [0, $e];
    }
}


//**** solidario */

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

function Obtener_Datos_Credito($cedula_ECrip, $fecha, $celular, $sueldo)
{
    try {
        $fecha_formateada = $fecha;
        $ingresos = $sueldo;
        $Instruccion = "SECU";
        $CELULAR = $celular;

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

        // var_dump($response_array);
        // var_dump($error);
        // var_dump($verboseLog);

        // if (extension_loaded('curl')) {
        //     echo "cURL está habilitado en este servidor.";
        // } else {
        //     echo "cURL no está habilitado en este servidor.";
        // }

        // Verificar si hay un error en la respuesta
        if (isset($response_array['esError'])) {
            // $GUARDAR = Guardar_Datos_Banco($response_array, $ID_UNICO);
            return [1, $response_array];
        } else {
            // $INC = $this->INCIDENCIAS($_inci);
            return [0, $response_array, $data, $error, extension_loaded('curl')];
        }
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
