<?php

require('fpdf/fpdf.php');


if (isset($_GET["cedula"]) && isset($_GET["numero"])) {
    $CEDULA = trim($_GET["cedula"]);
    $NUMERO = trim($_GET["numero"]);
    if ($CEDULA != null || $CEDULA != "" || $NUMERO != null || $NUMERO != "") {

        $longitud = strlen($CEDULA);
        $longitud_telefono = strlen($NUMERO);

        // echo "La longitud del string es: " . $longitud;
        if ($longitud == 9) {
            $CEDULA = "0" . $CEDULA;
        }
        if ($longitud == 9) {
            $NUMERO = "0" . $NUMERO;
        }

        Principal($CEDULA, $NUMERO);
    } else {
        $res = array(
            "SUCCESS" => "0",
            "MENSAJE" => "CEDULA NO VALIDA"
        );

        echo json_encode($res);
        exit();
    }
}

function Principal($CEDULA)
{
    $C = Guardar_Cedula($CEDULA);
    $EN = OBTENER_ENCRIPT($CEDULA);
    // echo json_encode($EN);
    // exit();

    if ($EN[0] == 1) {
        $ENCRY = $EN[1][0]["cedula_encrypt"];
        $API = CONSULTA_API_REG_DEMOGRAFICO($ENCRY);
        Generar_pdf($API[1]);
        echo json_encode($API[1]);
        exit();
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

            $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM_PROVINCIA"] = explode('/', $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM"])[0];
            $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM_CIUDAD"] = explode('/', $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM"])[1];
            $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM_PARROQUIA"] = explode('/', $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM"])[2];


            return [1, $data];
        }
        // Cerrar cURL
        curl_close($ch);
    } catch (Exception $e) {
        $e = $e->getMessage();
        return [0, $e];
    }
}

function Generar_pdf($API)
{
    $cedula = $API["SOCIODEMOGRAFICO"][0]["IDENTIFICACION"];
    $nombre = $API["SOCIODEMOGRAFICO"][0]["NOMBRE"];

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
    Declaración de Capacidad legal y sobre la Aceptación:\n
    Por medio de la presente autorizo de manera libre, voluntaria, previa, informada e inequívoca a BANCO SOLIDARIO
    S.A. para que en los términos legalmente establecidos realice el tratamiento de mis datos personales como parte de
    la relación precontractual, contractual y post contractual para: \n
    El procesamiento, análisis, investigación, estadísticas, referencias y demás trámites para facilitar, promover, permitir
    o mantener las relaciones con el BANCO. \n
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
    cesionario o endosatario. \n
    Tratar, transferir y/o entregar la información que se obtenga en virtud de esta solicitud incluida la relacionada con mi
    comportamiento crediticio y la que se genere durante la relación jurídica o comercial a autoridades competentes,
    terceros, socios comerciales y/o adquirientes de cartera, para el tratamiento de mis datos personales conforme los
    fines detallados en esta autorización o que me contacten por cualquier medio para ofrecerme los distintos servicios y
    productos que integran su portafolio y su gestión, relacionados o no con los servicios financieros del BANCO. En caso
    de que el BANCO ceda o transfiera cartera adeudada por mí, el cesionario o adquiriente de dicha cartera queda desde
    ahora expresamente facultado para realizar las mismas actividades establecidas en esta autorización.\n
    Entiendo y acepto que mi información personal podrá ser almacenada de manera impresa o digital, y accederán a ella
    los funcionarios de BANCO SOLIDARIO, estando obligados a cumplir con la legislación aplicable a las políticas de
    confidencialidad, protección de datos y sigilo bancario. En caso de que exista una negativa u oposición para el
    tratamiento de estos datos, no podré disfrutar de los servicios o funcionalidades que el BANCO ofrece y no podrá
    suministrarme productos, ni proveerme sus servicios o contactarme y en general cumplir con varias de las finalidades
    descritas en la Política. \n
    El BANCO conservará la información personal al menos durante el tiempo que dure la relación comercial y el que sea
    necesario para cumplir con la normativa respectiva del sector relativa a la conservación de archivos. \n
    Declaro conocer que para el desarrollo de los propósitos previstos en el presente documento y para fines
    precontractuales, contractuales y post contractuales es indispensable el tratamiento de mis datos personales
    conforme a la Política disponible en la página web del BANCO www.banco-solidario.com/transparencia Asimismo,
    declaro haber sido informado por el BANCO de los derechos con que cuento para conocer, actualizar y rectificar mi
    información personal; así como, si no deseo continuar recibiendo información comercial y/o publicidad, deberé remitir
    mi requerimiento a través del proceso de atención de derechos ARSO+ en cualquier momento y sin costo alguno,
    utilizando la página web (www.banco-solidario.com), teléfono: 1700 765 432, comunicado escrito o en cualquiera de
    las agencias del BANCO. \n
    En virtud de que, para ciertos productos y servicios el BANCO requiere o solicita el tratamiento de datos personales
    de un tercero que como cliente podré facilitar, como por ejemplo referencias comerciales o de contacto, garantizo
    que, si proporciono datos personales de terceras personas, les he solicitado su aceptación e informado acerca de las
    finalidades y la forma en la que el BANCO necesita tratar sus datos personales. \n
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
    información será conservada por el plazo estipulado en la normativa aplicable. \n
    Así mismo, declaro haber sido informado por el BANCO de los derechos con que cuento para conocer, actualizar y
    rectificar mi información personal, así como, los establecidos en el artículo 20 de la LOPDP y remitir mi requerimiento
    a través del proceso de atención de derechos ARSO+; en cualquier momento y sin costo alguno, utilizando la página
    web (www.banco-solidario.com), teléfono: 1700 765 432, comunicado escrito o en cualquiera de las agencias del
    BANCO. \n
    Para proteger esta información conozco que el Banco cuenta con medidas técnicas y organizativas de seguridad
    adaptadas a los riesgos como, por ejemplo: anonimización, cifrado, enmascarado y seudonimización. \n
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
    $pdf->Cell(0, 5, '      CLIENTE: ', 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, "      " . utf8_decode($nombre) . " - " . $cedula, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, "      " . utf8_decode('ACEPTÓ TERMINOS Y CONDICIONES: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, "      " . $fechaConsulta, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, utf8_decode('      DIRECCIÓN IP: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6,  "      " . $ip, 0, 1, 'L');


    $nombreArchivo = $cedula . "_" . $fecha . ".pdf"; // Nombre del archivo PDF
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

function getRealIP()
{
    if (!empty($_SERVER['HTTP_CLIENT_IP']))
        return $_SERVER['HTTP_CLIENT_IP'];

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
        return $_SERVER['HTTP_X_FORWARDED_FOR'];

    return $_SERVER['REMOTE_ADDR'];
}

