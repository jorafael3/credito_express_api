<?php
include 'funciones.php';

if (
    isset($_GET["cedula"]) && isset($_GET["numero"])
    && isset($_GET["fecha"])
    && isset($_GET["ingresos"])
    && isset($_GET["instr"])
    && isset($_GET["tipo"])
    && isset($_GET["dactilar"])
) {
    $CEDULA = trim($_GET["cedula"]);
    $NUMERO = trim($_GET["numero"]);
    $FECHA = trim($_GET["fecha"]);
    $INGRESOS = trim($_GET["ingresos"]);
    $instr = trim($_GET["instr"]);
    $tipo = trim($_GET["tipo"]);
    $dactilar = trim($_GET["dactilar"]);


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
                
                $C = Guardar_Cedula($CEDULA);
                $EN = OBTENER_ENCRIPT($CEDULA);
                $ENCRY = trim($EN[1][0]["cedula_encrypt"]);
                $API = CONSULTA_API_REG_DEMOGRAFICO(trim($ENCRY));
                $API[1]["SOCIODEMOGRAFICO"][0]["NUMERO_CHAT"] = $NUMERO;
                $API[1]["SOCIODEMOGRAFICO"][0]["DACTILAR_2"] = $dactilar;
                // $DATOS_CREDITO = Obtener_Datos_Credito($CEN[1], $FECHA, $NUMERO, '500');
                $DATOS_CREDITO  = Consulta_api($CEN[1], $NUMERO, $FECHA, $INGRESOS, $instr, $tipo);
                $GUARDAR = Guardar_Datos($CEDULA, $NUMERO, [], $ID_UNICO, "SOLIDARIO");
                $PDF = Generar_pdf2($API[1], $ID_UNICO);
                $DATOS_CREDITO[1]["LINK"] = "https://creditoexpres.com/api/docs/" . $ID_UNICO . ".pdf";
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
        // $url = 'https://bs-autentica.com/cco/apiofertacco/api/CasasComerciales/GenerarCalificacionEnPuntaCasasComerciales';
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
                    // $response_array['datos'] = $data;
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


function Generar_pdf2($API, $ID_UNICO)
{
    $cedula = $API["SOCIODEMOGRAFICO"][0]["IDENTIFICACION"];
    $nombre = $API["SOCIODEMOGRAFICO"][0]["NOMBRE"];
    $INDIVIDUAL_DACTILAR = $API["SOCIODEMOGRAFICO"][0]["DACTILAR_2"];
    $NUMERO = $API["SOCIODEMOGRAFICO"][0]["NUMERO_CHAT"];
    // $fechaConsulta = new Date();
    $ip = getRealIP();

    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();

    // Título
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, utf8_decode('AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES'), 0, 1, 'C');
    $pdf->Cell(0, 2, utf8_decode('BANCO SOLIDARIO S.A.'), 0, 1, 'C');
    $pdf->Ln(3);

    // Contenido
    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
    Por medio de la presente autorizo de manera libre, voluntaria, previa, informada e inequívoca a BANCO SOLIDARIO
    S.A. para que en los términos legalmente establecidos realice el tratamiento de mis datos personales como parte de
    la relación precontractual, contractual y post contractual para:\n
    El procesamiento, análisis, investigación, estadísticas, referencias y demás trámites para facilitar, promover, permitir
    o mantener las relaciones con el BANCO.\n
    Cuantas veces sean necesarias, gestione, obtenga y valide de cualquier entidad pública y/o privada que se encuentre
    facultada en el país, de forma expresa a la Dirección General de Registro Civil, Identificación y Cedulación, a la Dirección
    Nacional de Registros Públicos, al Servicio de Referencias Crediticias, a los burós de información crediticia, instituciones
    financieras de crédito, de cobranza, compañías emisoras o administradoras de tarjetas de crédito, personas naturales
    y los establecimientos de comercio, personas señaladas como referencias, empleador o cualquier otra entidad y demás
    fuentes legales de información autorizadas para operar en el país, información y/o documentación relacionada con mi
    perfil, capacidad de pago y/o cumplimiento de obligaciones, para validar los datos que he proporcionado, y luego de
    mi aceptación sean registrados para el desarrollo legítimo de la relación jurídica o comercial, así como para realizar
    actividades de tratamiento sobre mi comportamiento crediticio, manejo y movimiento de cuentas bancarias, tarjetas
    de crédito, activos, pasivos, datos/referencias personales y/o patrimoniales del pasado, del presente y las que se
    generen en el futuro, sea como deudor principal, codeudor o garante, y en general, sobre el cumplimiento de mis
    obligaciones. Faculto expresamente al Banco para transferir o entregar a las mismas personas o entidades, la
    información relacionada con mi comportamiento crediticio. Esta expresa autorización la otorgo al Banco o a cualquier
    cesionario o endosatario.\n
    Tratar, transferir y/o entregar la información que se obtenga en virtud de esta solicitud incluida la relacionada con mi
    comportamiento crediticio y la que se genere durante la relación jurídica o comercial a autoridades competentes,
    terceros, socios comerciales y/o adquirientes de cartera, para el tratamiento de mis datos personales conforme los
    fines detallados en esta autorización o que me contacten por cualquier medio para ofrecerme los distintos servicios y
    productos que integran su portafolio y su gestión, relacionados o no con los servicios financieros del BANCO. En caso
    de que el BANCO ceda o transfiera cartera adeudada por mí, el cesionario o adquiriente de dicha cartera queda desde
    ahora expresamente facultado para realizar las mismas actividades establecidas en esta autorización.
    Entiendo y acepto que mi información personal podrá ser almacenada de manera impresa o digital, y accederán a ella
    los funcionarios de BANCO SOLIDARIO, estando obligados a cumplir con la legislación aplicable a las políticas de
    confidencialidad, protección de datos y sigilo bancario. En caso de que exista una negativa u oposición para el
    tratamiento de estos datos, no podré disfrutar de los servicios o funcionalidades que el BANCO ofrece y no podrá
    suministrarme productos, ni proveerme sus servicios o contactarme y en general cumplir con varias de las finalidades
    descritas en la Política.\n
    El BANCO conservará la información personal al menos durante el tiempo que dure la relación comercial y el que sea
    necesario para cumplir con la normativa respectiva del sector relativa a la conservación de archivos.
    Declaro conocer que para el desarrollo de los propósitos previstos en el presente documento y para fines
    precontractuales, contractuales y post contractuales es indispensable el tratamiento de mis datos personales
    conforme a la Política disponible en la página web del BANCO www.banco-solidario.com/transparencia Asimismo,
    declaro haber sido informado por el BANCO de los derechos con que cuento para conocer, actualizar y rectificar mi
    información personal; así como, si no deseo continuar recibiendo información comercial y/o publicidad, deberé remitir
    mi requerimiento a través del proceso de atención de derechos ARSO+ en cualquier momento y sin costo alguno,
    utilizando la página web (www.banco-solidario.com), teléfono: 1700 765 432, comunicado escrito o en cualquiera de
    las agencias del BANCO.\n
    En virtud de que, para ciertos productos y servicios el BANCO requiere o solicita el tratamiento de datos personales
    de un tercero que como cliente podré facilitar, como por ejemplo referencias comerciales o de contacto, garantizo
    que, si proporciono datos personales de terceras personas, les he solicitado su aceptación e informado acerca de las
    finalidades y la forma en la que el BANCO necesita tratar sus datos personales.
    Para la comunicación de sus datos personales se tomarán las medidas de seguridad adecuadas conforme la normativa
    vigente.\n
   
    ");
    $pdf->MultiCell(0, 4, $contenido);
    $pdf->Ln(3);

    $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, utf8_decode('AUTORIZACIÓN EXPLÍCITA DE TRATAMIENTO DE DATOS PERSONALES'), 0, 1, 'C');
    $pdf->Cell(0, 2, utf8_decode('BANCO SOLIDARIO S.A.'), 0, 1, 'C');
    $pdf->Ln(3);

    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
    Declaro que soy el titular de la información reportada, y que la he suministrado de forma voluntaria, completa,
    confiable, veraz, exacta y verídica:\n
    Como titular de los datos personales, particularmente el código dactilar, dato biométrico facial, no me encuentro
    obligado a otorgar mi autorización de tratamiento a menos que requiera consultar y/o aplicar a un producto y/o
    servicio financiero. A través de la siguiente autorización libre, especifica, previa, informada, inequívoca y explícita,
    faculto al tratamiento (recopilación, acceso, consulta, registro, almacenamiento, procesamiento, análisis, elaboración
    de perfiles, comunicación o transferencia y eliminación) de mis datos personales incluido el código dactilar con la
    finalidad de: consultar y/o aplicar a un producto y/o servicio financiero y ser sujeto de decisiones basadas única o
    parcialmente en valoraciones que sean producto de procesos automatizados, incluida la elaboración de perfiles. Esta
    información será conservada por el plazo estipulado en la normativa aplicable.\n
    Así mismo, declaro haber sido informado por el BANCO de los derechos con que cuento para conocer, actualizar y
    rectificar mi información personal, así como, los establecidos en el artículo 20 de la LOPDP y remitir mi requerimiento
    a través del proceso de atención de derechos ARSO+; en cualquier momento y sin costo alguno, utilizando la página
    web (www.banco-solidario.com), teléfono: 1700 765 432, comunicado escrito o en cualquiera de las agencias del
    BANCO.\n
    Para proteger esta información conozco que el Banco cuenta con medidas técnicas y organizativas de seguridad
    adaptadas a los riesgos como, por ejemplo: anonimización, cifrado, enmascarado y seudonimización.\n
    Con la lectura de este documento manifiesto que he sido informado sobre el Tratamiento de mis Datos Personales, y
    otorgo mi autorización y aceptación de forma voluntaria y verídica. En señal de aceptación suscribo el presente
    documento. 
    ");

    $pdf->MultiCell(0, 4, $contenido);
    $pdf->Ln(3);

    date_default_timezone_set('America/Guayaquil');

    $fechaConsulta = date('Y-m-d H:i:s');
    $fecha = date('YmdHis');
    // $fecha = DateTime::createFromFormat('YmdHis', $fechaConsulta);
    // $fechaFormateada = $fecha->format('Y-m-d H:i A');
    // Información del cliente
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, utf8_decode('CÉDULA:  ') . $cedula, 0, 1, 'L');
    // $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, "NOMBRES COMPLETOS:  " . utf8_decode($nombre), 0, 1, 'L');
    // $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, "CODIGO DACTILAR:  " . utf8_decode($INDIVIDUAL_DACTILAR), 0, 1, 'L');
    // $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, utf8_decode("NÚMERO CELULAR:  ") . $NUMERO, 0, 1, 'L');
    // $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, utf8_decode("FECHA ACEPTÓ TÉRMINOS Y CONDICIONES:  ") . $fechaConsulta, 0, 1, 'L');
    // $pdf->SetFont('Arial', '', 10);
    // $pdf->Cell(0, 6,  "      " . $ip, 0, 1, 'L');


    $nombreArchivo = $ID_UNICO . ".pdf"; // Nombre del archivo PDF
    $rutaCarpeta = 'docs/'; // Ruta de la carpeta donde se guardará el archivo (debes cambiar esto)

    if (chmod($rutaCarpeta, 0777)) {
        // echo "Permisos cambiados exitosamente.";
    }

    $pdf->Output($rutaCarpeta . $nombreArchivo, 'F');

    // try {
    //     $cedula = trim($param["cedula"]);
    //     $query = $this->db->connect_dobra()->prepare('UPDATE creditos_solicitados
    //     set ruta_archivo = :ruta_archivo
    //     where cedula = :cedula
    //     ');
    //     $query->bindParam(":ruta_archivo", $nombreArchivo, PDO::PARAM_STR);
    //     $query->bindParam(":cedula", $cedula, PDO::PARAM_STR);
    //     if ($query->execute()) {
    //         $result = $query->fetchAll(PDO::FETCH_ASSOC);
    //         echo json_encode(1);
    //         exit();
    //         // return 1;
    //     } else {
    //         // return 0;
    //     }
    // } catch (PDOException $e) {
    //     $e = $e->getMessage();
    //     echo json_encode($e);
    //     exit();
    // }
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
