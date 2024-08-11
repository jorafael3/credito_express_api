<?php

// require('fpdf/fpdf.php');
require('fpdf/WriteHTML.php');


if (isset($_GET["cedula"]) && isset($_GET["numero"]) && isset($_GET["key"]) && isset($_GET["terminos"])) {
    $CEDULA = trim($_GET["cedula"]);
    $NUMERO = trim($_GET["numero"]);
    $TERMINOS = trim($_GET["terminos"]);
    $key = trim($_GET["key"]);
    $KEY = "7uXvhfOAUNbmfiKnzVlSq4uJRj0tx5G2";

    if ($KEY == $key) {
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

            Principal($CEDULA, $NUMERO, $TERMINOS);
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

function Principal($CEDULA, $NUMERO, $TERMIMOS)
{
    $C = Guardar_Cedula($CEDULA);
    $EN = OBTENER_ENCRIPT($CEDULA);
    // echo json_encode($EN);
    // exit();

    if ($EN[0] == 1) {
        $ENCRY = $EN[1][0]["cedula_encrypt"];
        $API = CONSULTA_API_REG_DEMOGRAFICO($ENCRY);

        $ESPLENDOR = "SPLENDOR";
        $HICAR = "HICAR";
        $MISUPERS = "MISUPERS";


        if (trim($TERMIMOS) == $ESPLENDOR) {
            Generar_pdf_SPLENDOR($API[1], trim($CEDULA), $NUMERO, $ESPLENDOR);
        }
        if (trim($TERMIMOS) == $HICAR) {
            Generar_pdf_HICARS($API[1], trim($CEDULA), $NUMERO, $HICAR);
        }
        if (trim($TERMIMOS) == $MISUPERS) {
            Generar_pdf_MISUPERS($API[1], trim($CEDULA), $NUMERO, $MISUPERS);
        }
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
                    "ERROR" => true,
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

//** SPLENDOR ***/

function Generar_pdf_SPLENDOR($API, $CEDULA, $NUMERO, $EMP)
{
    $cedula = $API["SOCIODEMOGRAFICO"][0]["IDENTIFICACION"];
    $nombre = $API["SOCIODEMOGRAFICO"][0]["NOMBRE"];

    // $fechaConsulta = new Date();
    $ip = getRealIP();

    $pdf = new PDF_HTML('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetLeftMargin(15);
    $pdf->SetRightMargin(15);
    $pdf->SetAutoPageBreak(true, 15);
    // Título
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, utf8_decode('AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES Y TÉRMINOS Y CONDICIONES'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
        Declaro concurrir de manera libre y voluntaria a la aceptación del presente instrumento, y que no he sido
        inducido/a a error, fuerza, dolo, temor reverencial o coerción alguna que pueda viciar mi consentimiento. 
    ");
    $pdf->MultiCell(0, 4, $contenido);
    $pdf->Ln(3);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 2, utf8_decode('I.'), 0, 1, 'C');
    $pdf->Ln(3);
    $pdf->Cell(0, 2, utf8_decode('AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES'), 0, 1, 'C');
    $pdf->Ln(3);

    // Contenido
    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
        Expreso mi consentimiento libre, específico, informado e inequívoco para proporcionar mis datos
        personales y autorizo a la compañía SERVIMATERIALES S.A, con Registro Único de Contribuyentes (RUC)
        Nro. 0992883138001, (En adelante Encargado del tratamiento) para que efectúe el tratamiento de los
        mismos con las finalidades y en las formas establecidas en el presente instrumento.\n
        1. TRATAMIENTO DE DATOS: Acepto y autorizo para que SERVIMATERIALES realice la recogida,
        recopilación, procesamiento, obtención, registro, organización, estructuración, conservación,
        almacenamiento, consulta, adaptación, modificación, indexación, extracción, interconexión, utilización,
        posesión, aprovechamiento, acceso, cesión, comunicación, verificación, elaboración de perfiles,
        transferencia y eliminación de mis datos personales.
        Para tales efectos, acepto que SERVIMATERIALES podrá gestionar, obtener, cruzar y validar los datos
        personales que he proporcionado con cualquier entidad pública y/o privada que se encuentre debidamente
        facultada en el país, y, en especial, con la Dirección General de Registro Civil, Identificación y Cedulación,
        la Dirección Nacional de Registros Públicos, así como de otras fuentes legales de información autorizadas
        para operar en el país, de acceso público o a las que SERVIMATERIALES tenga legítimo acceso, y luego de
        mi aceptación puedan ser registrados para el desarrollo legítimo de la relación jurídica o comercial.
        Comprendo y acepto que mi información personal podrá ser almacenada de manera impresa o digital, y
        accederán a ella los funcionarios de SERVIMATERIALES, estando obligados a cumplir con la legislación
        aplicable a las políticas de confidencialidad y protección de datos.\n
        2. FINALIDADES DEL TRATAMIENTO: Conozco y acepto que el tratamiento de mis datos tiene como
        finalidad el procesamiento, análisis, generación de reportes demográficos y de estadísticas, estudios de
        mercado y recepción de anuncios y/o publicidad respecto a los servicios y/o productos que consulte, a fin
        de facilitar, promover, permitir o mantener relaciones con el Proveedor de dichos servicios y/o productos,
        quien se considerará como Responsable del tratamiento de mis datos personales. Consecuentemente,
        reconozco que el Responsable del tratamiento de mis datos es SERVIMATERIALES S.A., con RUC Nro.
        0992883138001, a quien podré contactar al correo: info@servimateriales.com y/o al número telefónico:
        +593 99 333 8881.\n
        3. DATOS PERSONALES: Comprendo y autorizo que, para el cumplimiento del presente instrumento, se
        me podrá solicitar la siguiente información personal: nombres y apellidos, número de identificación,
        dirección de correo electrónico, número telefónico, estado civil, edad, fecha de nacimiento, nacionalidad,
        idioma de origen, formación o instrucción académica, títulos profesionales, lugar de trabajo, cargo o
        función desempeñado y datos sensibles como identidad de género, identidad cultural, condición migratoria
        y datos relativos a salud. Como titular de dichos datos, conozco que no estoy obligado/a a aceptar la
        presente autorización ni proporcionar dicha información a menos que requiera consultar y/o acceder a los
        productos y/o servicios de mi interés.\n
        4. DERECHOS: Declaro conocer los derechos que me asisten y que la normativa vigente prevé para acceder,
        rectificar, actualizar, solicitar la eliminación, oponerme, dirigir quejas y recibir los datos personales que he
        proporcionado y que se encuentren en tratamiento. Así mismo, declaro conocer que puedo revocar mi
        consentimiento por escrito, en cualquier momento, sin que sea necesaria una justificación. Para tales fines,
        podré remitir mi requerimiento al correo info@servimateriales.com en concordancia con los
        procedimientos establecidos en la Ley Orgánica de Protección de Datos Personales (LOPDP) y su
        Reglamento, así como las políticas internas de SERVIMATERIALES.\n
        5. CONSERVACIÓN DE DATOS PERSONALES: SERVIMATERIALES conservará los datos personales durante
        un tiempo no mayor al necesario para cumplir con las finalidades de su tratamiento y el que sea necesario
        para el cumplimiento de disposiciones legales.\n
        6. MEDIDAS DE SEGURIDAD Y CONFIDENCIALIDAD: Conozco que SERVIMATERIALES se compromete a
        implementar y aplicar medidas técnicas y organizativas, adecuadas y necesarias, para la protección de mis
        datos personales, y, especialmente, para evaluar, prevenir, impedir, mitigar y controlar riesgos, amenazas
        y vulnerabilidades; obligación que se hace extensiva al Responsable del tratamiento, de conformidad con
        la LOPDP. Reconozco y acepto que este compromiso no impedirá el tratamiento de mis datos personales
        para las finalidades señaladas.\n
        7. OTRAS DECLARACIONES: Declaro ser el titular de la información reportada; que el tratamiento de mis
        datos personales es legítimo y lícito, el cual se fundamenta en mi consentimiento expreso, libre, específico,
        informado e inequívoco; y, que las finalidades previstas en este instrumento para el tratamiento de sus
        datos personales son determinadas, explícitas, legítimas y que me han sido debidamente comunicadas. De
        igual manera declaro que los datos personales susceptibles a tratamiento son pertinentes y limitados al
        cumplimiento de las finalidades previstas en este instrumento; que el tratamiento es adecuado, necesario,
        oportuno, relevante, y no excesivo, para las finalidades señaladas; que todos los datos personales
        proporcionados son correctos, exactos, íntegros, precisos, completos, comprobables y claros; y, que ha
        sido informado/a acerca de mis derechos como titular y del ejercicio de los mismos.
    ");
    $pdf->MultiCell(0, 4, $contenido);
    $pdf->Ln(3);

    // $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, utf8_decode('II.'), 0, 1, 'C');
    $pdf->Ln(3);
    $pdf->Cell(0, 2, utf8_decode('TÉRMINOS Y CONDICIONES'), 0, 1, 'C');
    $pdf->Ln(3);

    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
        1. DEFINICIÓN Y SERVICIOS: El Chatbot es un asistente virtual operado por SERVIMATERIALES S.A. (En
        adelante SERVIMATERIALES) que permite al USUARIO la consulta de los productos y/o servicios ofertados
        o ya contratados de SERVIMATERIALES S.A., con RUC Nro. 0992883138001, en su calidad de PROVEEDOR.
        El Chatbot no requiere ni procesa el pago de productos y/o servicios; por lo que el USUARIO se abstendrá
        de enviar comprobantes de pago u otras transacciones a través del Chatbot. El uso y/o acceso al Chatbot
        por parte del USUARIO no generará costo alguno de ninguna índole.\n
        2. MANEJO DE LA INFORMACIÓN: Sin perjuicio de los datos personales expresados en la Sección I de este
        instrumento, el Chatbot no solicita información como claves, números de cuentas que el USUARIO
        mantenga en instituciones del Sistema Financiero Nacional, números de tarjetas de crédito o de débito,
        reconocimiento facial, código dactilar, huellas u otros datos de carácter confidencial, personal, financiero
        o crediticio del USUARIO. El USUARIO es el único responsable de la veracidad y exactitud de la información
        proporcionada y se abstendrá de proporcionar información no requerida por Chatbot, de manera que
        exime a Chatbot de cualquier responsabilidad contractual o extracontractual, así como de los daños y
        perjuicios que se pudieren ocasionar por el envío de dicha información. No obstante, el Chatbot empleará
        las medidas técnicas de seguridad necesarias para la protección de la información proporcionada por el
        USUARIO y evitar su divulgación y/o uso indebido.\n
        3. CANALES Y HORARIOS: Chatbot funciona vía WHATSAPP®, redirigido desde anuncios en plataformas
        publicitarias de las redes sociales habilitadas para el efecto hasta el momento como: Facebook, Instagram
        y Tiktok. El Chatbot estará disponible las 24 horas del día, los siete días de la semana; no obstante, el
        USUARIO reconoce que, dependiendo del horario en que se realicen las operaciones, estas podrán ser
        procesadas el siguiente día hábil.\n
        4. LÍMITES Y REFORMAS: SERVIMATERIALES podrá modificar los límites y condiciones de los servicios
        brindados a través del Chatbot, como: número máximo de consultas, tiempo de conexión, tipo de consultas
        u operaciones que podrán efectuarse, entre otros que SERVIMATERIALES determine. Dichos límites y
        condiciones entrarán en vigencia y su aplicación será obligatoria para el USUARIO desde que le fueren
        comunicados, a través del Chatbot u otros canales que, en su momento, estuvieren habilitados. Así mismo,
        SERVIMATERIALES se reserva el derecho de modificar los términos y condiciones, los que serán efectivos
        desde su aceptación por parte del USUARIO.\n
        5. MANTENIMIENTO Y SOPORTE: SERVIMATERIALES será responsable del funcionamiento del Chatbot, de
        acuerdo a los controles y resguardos físicos y tecnológicos para su uso seguro, considerando los riesgos
        inherentes a su operatividad. Esta responsabilidad no comprende brindar mantenimiento a los dispositivos,
        plataformas, sistemas, aplicaciones o redes desde las cuales opere el Chatbot.
        6. PRIVACIDAD: SERVIMATERIALES empleará las medidas necesarias para proteger la integridad de la
        información que le ha sido proporcionada, guardando la confidencialidad de la misma, evitando su
        divulgación y/o mal uso a través de las normas técnicas de seguridad requeridas por la ley y según la
        naturaleza de la información lo amerite.\n
        7. LÍMITE DE RESPONSABILIDAD: SERVIMATERIALES no responderá por los daños o perjuicios, directos,
        indirectos o de cualquier naturaleza que el USUARIO pudiera sufrir por las siguientes circunstancias: 7.1. Si
        la consulta u operación requerida por el USUARIO no puede procesarse por no encuadrarse en los
        parámetros prefijados por SERVIMATERIALES. En este sentido, el USUARIO reconoce que el Chatbot es un
        software que ha sido programado por SERVIMATERIALES bajo cierto formato y parámetros
        predeterminados; por lo que cualquier consulta u operación que no se encuadren en dicho formato y
        parámetros, no serán procesados. 7.2. Por incompatibilidad del dispositivo usado por el USUARIO. 7.3. Por
        virus, ataques cibernéticos, vulneraciones, filtraciones u otras intrusiones de seguridad que afecten la
        plataforma de mensajería WHATSAPP®. 7.4. Por problemas o errores operativos o de conectividad de tal
        plataforma o por restricciones impuestas por autoridades administrativas o judiciales para su
        funcionamiento. 7.5. Por la sustracción, pérdida, deterioro o destrucción del dispositivo desde el cual se
        accede al Chatbot. 7.6. Por la sustracción de información o ingreso de la misma por parte de terceros
        diferentes al USUARIO, titular de dicha información. SERVIMATERIALES presume que el USUARIO que
        realiza la consulta u operación es el titular de la información que proporciona y/o requiere o, en su defecto,
        que ha sido expresamente autorizado por el titular de la misma para proporcionarla y/o para recibirla del
        Chatbot. 7.7. Por la calidad del servicio de telefonía o de Internet utilizado para la conectividad y acceso al
        Chatbot. 7.8. Por el uso o destino que el USUARIO dé a la información recibida u operaciones realizadas a
        través del Chatbot. 7.9. Por la calidad, precios y atención respecto a los productos y/o servicios del
        PROVEEDOR, ya que los mismos no son ofertados por SERVIMATERIALES. El USUARIO comprende y
        reconoce que SERVIMATERIALES y el PROVEEDOR son personas jurídicas distintas y, por ende, no existe
        relación comercial o vinculación alguna entre ellas para la oferta de los productos y servicios que le
        pertenecen al PROVEEDOR. La intervención de SERVIMATERIALES se limita única y exclusivamente a la
        operatividad del Chatbot como medio para de realización de consultas y operaciones relacionadas a
        productos y/o servicios de terceros. 7.10. Por cualquier otro hecho u actuación no imputable a
        SERVIMATERIALES.\n
        8. AUTORIZACIÓN: El USUARIO autoriza expresamente a SERVIMATERIALES a grabar, captar, registrar y/o
        almacenar los datos y respuestas dadas en el Chatbot, así como las solicitudes, autorizaciones y, en general,
        las instrucciones impartidas a través de tal medio y a reproducirlas y/o mostrarlas en la medida que sea
        necesario aclarar, explicar, demostrar, probar y/o verificar sus actividades en el Chatbot, en especial, ante
        cualquier autoridad fiscal o judicial.
        Declaro haber leído y comprendido cada una de las cláusulas y demás declaraciones que constan en este
        instrumento sobre la autorización y tratamiento de mis datos personales, así como sobre la operatividad
        del Chatbot. Consecuentemente, con pleno conocimiento de las obligaciones y compromisos que me
        corresponden, acepto el servicio Chatbot bajo los términos y condiciones que me han sido expuestos.\n
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
    $pdf->Cell(0, 6, "      " . utf8_decode($nombre) . " - " . $CEDULA, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, "      " . utf8_decode('ACEPTÓ TERMINOS Y CONDICIONES: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, "      " . $fechaConsulta, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, utf8_decode('      DIRECCIÓN IP: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6,  "      " . $ip, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, utf8_decode('      NUMERO: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6,  "      " . $NUMERO, 0, 1, 'L');


    $nombreArchivo = $CEDULA . "_" . $fecha . "_" . $EMP . ".pdf"; // Nombre del archivo PDF
    $rutaCarpeta = 'docs/'; // Ruta de la carpeta donde se guardará el archivo (debes cambiar esto)

    if (chmod($rutaCarpeta, 0777)) {
        // echo "Permisos cambiados exitosamente.";
    }
    $pdf->Output($rutaCarpeta . $nombreArchivo, 'F');
}

//** HICAR */
function Generar_pdf_HICARS($API, $CEDULA, $NUMERO, $EMP)
{
    $cedula = $API["SOCIODEMOGRAFICO"][0]["IDENTIFICACION"];
    $nombre = $API["SOCIODEMOGRAFICO"][0]["NOMBRE"];

    // $fechaConsulta = new Date();
    $ip = getRealIP();

    $pdf = new PDF_HTML('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetLeftMargin(15);
    $pdf->SetRightMargin(15);
    $pdf->SetAutoPageBreak(true, 15);
    // Título
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, utf8_decode('AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES Y TÉRMINOS Y CONDICIONES'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
        Declaro concurrir de manera libre y voluntaria a la aceptación del presente instrumento, y que no he sido
        inducido/a a error, fuerza, dolo, temor reverencial o coerción alguna que pueda viciar mi consentimiento.  
    ");
    $pdf->MultiCell(0, 4, $contenido);
    $pdf->Ln(3);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 2, utf8_decode('I.'), 0, 1, 'C');
    $pdf->Ln(3);
    $pdf->Cell(0, 2, utf8_decode('AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES'), 0, 1, 'C');
    $pdf->Ln(3);

    // Contenido
    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
        Expreso mi consentimiento libre, específico, informado e inequívoco para proporcionar mis datos
        personales y autorizo a la compañía SERVIMATERIALES S.A, con Registro Único de Contribuyentes (RUC)
        Nro. 0992681845001, (En adelante Encargado del tratamiento) para que efectúe el tratamiento de los
        mismos con las finalidades y en las formas establecidas en el presente instrumento.\n
        1. TRATAMIENTO DE DATOS: Acepto y autorizo para que SERVIMATERIALES realice la recogida,
        recopilación procesamiento, obtención, registro, organización, estructuración, conservación,
        almacenamiento, consulta, adaptación, modificación, indexación, extracción, interconexión, utilización,
        posesión, aprovechamiento, acceso, cesión, comunicación, verificación, elaboración de perfiles,
        transferencia Y eliminación de mis datos personales.\n
        Para tales efectos, acepto que SERVIMATERIALES podrá gestionar, obtener, cruzar y validar los datos
        personales que he proporcionado con cualquier entidad pública y/o privada que se encuentre debidamente
        facultada en el país, y, en especial, con la Dirección General de Registro Civil, Identificación y Cedulación,
        la Dirección Nacional de Registros Públicos, así como de otras fuentes legales de información autorizadas
        para operar en el país, de acceso público o a las que SERVIMATERIALES tenga legítimo acceso, y luego de
        mi aceptación puedan ser registrados para el desarrollo legítimo de la relación jurídica o comercial.
        Comprendo y acepto que mi información personal podrá ser almacenada de manera impresa o digital, y
        accederán a ella los funcionarios de SERVIMATERIALES, estando obligados a cumplir con la legislación
        aplicable a las políticas de confidencialidad y protección de datos.\n
        2. FINALIDADES DEL TRATAMIENTO: Conozco y acepto que el tratamiento de mis datos tiene como
        finalidad el procesamiento, análisis, generación de reportes demográficos y de estadísticas, estudios de
        mercado y recepción de anuncios y/o publicidad respecto a los servicios y/o productos que consulte, a fin
        de facilitar, promover, permitir o mantener relaciones con el Proveedor de dichos servicios y/o productos,
        quien se considerará como Responsable del tratamiento de mmis datos personales. Consecuentemente,
        reconozco que el Responsable del tratamiento de mis datos es SAVINGGROUP S.A.S., con RUC Nro.
        0993346217001, a quien podré contactar al correo: gerenciacomercial@hicar.ec y/o al número telefónico:
        +593 99 592 4374.\n
        3. DATOS PERSONALES: Comprendo y autorizo que, para el cumplimiento del presente instrumento, se
        me podrá solicitar la siguiente información personal: nombres y apellidos, número de identificación,
        dirección de correo electrónico, número telefónico, estado civil, edad, fecha de nacimiento, nacionalidad,
        idioma de origen, formación o instrucción académica, títulos profesionales, lugar de trabajo, cargo o
        función desempeñado y datos sensibles como identidad de género, identidad cultural, condición migratoria
        y datos relativos a salud. Como titular de dichos datos, conozco que no estoy obligado/a a aceptar la
        presente autorización ni proporcionar dicha información a menos que requiera consultar y/o acceder a los
        productos y/o servicios de mi interés.\n
        4. DERECHOS: Declaro conocer los derechos que me asisten y que la normativa vigente prevé para acceder,
        rectificar, actualizar, solicitar la eliminación, oponerme, dirigir quejas y recibir los datos personales que he
        proporcionado y que se encuentren en tratamiento. Así mismo, declaro conocer que puedo revocar mi
        consentimiento por escrito, en cualquier momento, sin que sea necesaria una justificación. Para tales fines,
        podré remitir mi requerimiento al correo gerenciacomercial@hicar.ec en concordancia con los
        procedimientos establecidos en la Ley Orgánica de Protección de Datos Personales (LOPDP) y su
        Reglamento, así como las políticas internas de SERVIMATERIALES.\n
        5. CONSERVACIÓN DE DATOS PERSONALES: SERVIMATERIALES conservará los datos personales durante
        un tiempo no mayor al necesario para cumplir con las finalidades de su tratamiento y el que sea necesario
        para el cumplimiento de disposiciones legales.\n
        6. MEDIDAS DE SEGURIDAD Y CONFIDENCIALIDAD: Conozco que SERVIMATERIALES se compromete a
        implementar y aplicar medidas técnicas y organizativas, adecuadas y necesarias, para la protección de mis
        datos personales, y, especialmente, para evaluar, prevenir, impedir, mitigar y controlar riesgos, amenazas
        y vulnerabilidades; obligación que se hace extensiva al Responsable del tratamiento, de conformidad con
        la LOPDP. Reconozco y acepto que este compromiso no impedirá el tratamiento de mis datos personales
        para las finalidades señaladas.\n
        7. OTRAS DECLARACIONES: Declaro ser el titular de la información reportada; que el tratamiento de mis
        datos personales es legítimo y lícito, el cual se fundamenta en mi consentimiento expreso, libre, específico,
        informado e inequívoco; y, que las finalidades previstas en este instrumento para el tratamiento de sus
        datos personales son determinadas, explícitas, legítimas y que me han sido debidamente comunicadas. De
        igual manera declaro que los datos personales susceptibles a tratamiento son pertinentes y limitados al
        cumplimiento de las finalidades previstas en este instrumento; que el tratamiento es adecuado, necesario,
        oportuno, relevante, y no excesivo, para las finalidades señaladas; que todos los datos personales
        proporcionados son correctos, exactos, íntegros, precisos, completos, comprobables y claros; y, que ha
        sido informado/a acerca de mis derechos como titular y del ejercicio de los mismos.\n
    ");
    $pdf->MultiCell(0, 4, $contenido);
    $pdf->Ln(3);

    // $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, utf8_decode('II.'), 0, 1, 'C');
    $pdf->Ln(3);
    $pdf->Cell(0, 2, utf8_decode('TÉRMINOS Y CONDICIONES'), 0, 1, 'C');
    $pdf->Ln(3);

    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
        1. DEFINICIÓN Y SERVICIOS: El Chatbot es un asistente virtual operado por SERVIMATERIALES S.A. (En
        adelante SERVIMATERIALES) que permite al USUARIO la consulta de los productos y/o servicios ofertados
        o ya contratados de SAVINGGROUP S.A.S., con RUC Nro. 0993346217001, en su calidad de PROVEEDOR. El
        Chatbot no requiere ni procesa el pago de productos y/o servicios; por lo que el USUARIO se abstendrá de
        enviar comprobantes de pago u otras transacciones a través del Chatbot. El uso y/o acceso al Chatbot por
        parte del USUARIO no generará costo alguno de ninguna índole.\n
        2. MANEJO DE LA INFORMACIÓN: Sin perjuicio de los datos personales expresados en la Sección I de este
        instrumento, el Chatbot no solicita información como claves, números de cuentas que el USUARIO
        mantenga en instituciones del Sistema Financiero Nacional, números de tarjetas de crédito o de débito,
        reconocimiento facial, código dactilar, huellas u otros datos de carácter confidencial, personal, financiero
        o crediticio del USUARIO. El USUARIO es el único responsable de la veracidad y exactitud de la información
        proporcionada y se abstendrá de proporcionar información no requerida por Chatbot, de manera que
        exime a Chatbot de cualquier responsabilidad contractual o extracontractual, así como de los daños y
        perjuicios que se pudieren ocasionar por el envío de dicha información. No obstante, el Chatbot empleará 
        las medidas técnicas de seguridad necesarias para la protección de la información proporcionada por el
        USUARIO y evitar su divulgación y/o uso indebido.\n
        3. CANALES Y HORARIOS: Chatbot funciona vía WHATSAPP®, redirigido desde anuncios en plataformas
        publicitarias de las redes sociales habilitadas para el efecto hasta el momento como: Facebook, Instagram
        y Tiktok. El Chatbot estará disponible las 24 horas del día, los siete días de la semana; no obstante, el
        USUARIO reconoce que, dependiendo del horario en que se realicen las operaciones, estas podrán ser
        procesadas el siguiente día hábil.\n
        4. LÍMITES Y REFORMAS: SERVIMATERIALES podrá modificar los límites y condiciones de los servicios
        brindados a través del Chatbot, como: número máximo de consultas, tiempo de conexión, tipo de consultas
        u operaciones que podrán efectuarse, entre otros que SERVIMATERIALES determine. Dichos límites y
        condiciones entrarán en vigencia y su aplicación será obligatoria para el USUARIO desde que le fueren
        comunicados, a través del Chatbot u otros canales que, en su momento, estuvieren habilitados. Así mismo,
        SERVIMATERIALES se reserva el derecho de modificar los términos y condiciones, los que serán efectivos
        desde su aceptación por parte del USUARIO.\n
        5. MANTENIMIENTO Y SOPORTE: SERVIMATERIALES será responsable del funcionamiento del Chatbot, de
        acuerdo a los controles y resguardos físicos y tecnológicos para su uso seguro, considerando los riesgos
        inherentes a su operatividad. Esta responsabilidad no comprende brindar mantenimiento a los dispositivos,
        plataformas, sistemas, aplicaciones o redes desde las cuales opere el Chatbot.
        6. PRIVACIDAD: SERVIMATERIALES empleará las medidas necesarias para proteger la integridad de la
        información que le ha sido proporcionada, guardando la confidencialidad de la misma, evitando su
        divulgación y/o mal uso a través de las normas técnicas de seguridad requeridas por la ley y según la
        naturaleza de la información lo amerite. \n
        7. LÍMITE DE RESPONSABILIDAD: SERVIMATERIALES no responderá por los daños o perjuicios, directos,
        indirectos o de cualquier naturaleza que el USUARIO pudiera sufrir por las siguientes circunstancias: 7.1. Si
        la consulta u operación requerida por el USUARIO no puede procesarse por no encuadrarse en los
        parámetros prefijados por SERVIMATERIALES. En este sentido, el USUARIO reconoce que el Chatbot es un
        software que ha sido programado por SERVIMATERIALES bajo cierto formato y parámetros
        predeterminados; por lo que cualquier consulta u operación que no se encuadren en dicho formato y
        parámetros, no serán procesados. 7.2. Por incompatibilidad del dispositivo usado por el USUARIO. 7.3. Por
        virus, ataques cibernéticos, vulneraciones, filtraciones u otras intrusiones de seguridad que afecten la
        plataforma de mensajería WHATSAPP®. 7.4. Por problemas o errores operativos o de conectividad de tal
        plataforma o por restricciones impuestas por autoridades administrativas o judiciales para su
        funcionamiento. 7.5. Por la sustracción, pérdida, deterioro o destrucción del dispositivo desde el cual se
        accede al Chatbot. 7.6. Por la sustracción de información o ingreso de la misma por parte de terceros
        diferentes al USUARIO, titular de dicha información. SERVIMATERIALES presume que el USUARIO que
        realiza la consulta u operación es el titular de la información que proporciona y/o requiere o, en su defecto,
        que ha sido expresamente autorizado por el titular de la misma para proporcionarla y/o para recibirla del
        Chatbot. 7.7. Por la calidad del servicio de telefonía o de Internet utilizado para la conectividad y acceso al
        Chatbot. 7.8. Por el uso o destino que el USUARIO dé a la información recibida u operaciones realizadas a
        través del Chatbot. 7.9. Por la calidad, precios y atención respecto a los productos y/o servicios del
        PROVEEDOR, ya que los mismos no son ofertados por SERVIMATERIALES. El USUARIO comprende y
        reconoce que SERVIMATERIALES y el PROVEEDOR son personas jurídicas distintas y, por ende, no existe 
        relación comercial o vinculación alguna entre ellas para la oferta de los productos y servicios que le
        pertenecen al PROVEEDOR. La intervención de SERVIMATERIALES se limita única y exclusivamente a la
        operatividad del Chatbot como medio para de realización de consultas y operaciones relacionadas a
        productos y/o servicios de terceros. 7.10. Por cualquier otro hecho u actuación no imputable a
        SERVIMATERIALES.\n
        8. AUTORIZACIÓN: El USUARIO autoriza expresamente a SERVIMATERIALES a grabar, captar, registrar y/o
        almacenar los datos y respuestas dadas en el Chatbot, así como las solicitudes, autorizaciones y, en general,
        las instrucciones impartidas a través de tal medio y a reproducirlas y/o mostrarlas en la medida que sea
        necesario aclarar, explicar, demostrar, probar y/o verificar sus actividades en el Chatbot, en especial, ante
        cualquier autoridad fiscal o judicial.
        Declaro haber leído y comprendido cada una de las cláusulas y demás declaraciones que constan en este
        instrumento sobre la autorización y tratamiento de mis datos personales, así como sobre la operatividad
        del Chatbot. Consecuentemente, con pleno conocimiento de las obligaciones y compromisos que me
        corresponden, acepto el servicio Chatbot bajo los términos y condiciones que me han sido expuestos.\n
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
    $pdf->Cell(0, 6, "      " . utf8_decode($nombre) . " - " . $CEDULA, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, "      " . utf8_decode('ACEPTÓ TERMINOS Y CONDICIONES: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, "      " . $fechaConsulta, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, utf8_decode('      DIRECCIÓN IP: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6,  "      " . $ip, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, utf8_decode('      NUMERO: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6,  "      " . $NUMERO, 0, 1, 'L');


    $nombreArchivo = $CEDULA . "_" . $fecha . "_" . $EMP . ".pdf"; // Nombre del archivo PDF
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

function Generar_pdf_MISUPERS($API, $CEDULA, $NUMERO, $EMP)
{
    $cedula = $API["SOCIODEMOGRAFICO"][0]["IDENTIFICACION"];
    $nombre = $API["SOCIODEMOGRAFICO"][0]["NOMBRE"];

    // $fechaConsulta = new Date();
    $ip = getRealIP();

    $pdf = new PDF_HTML('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetLeftMargin(15);
    $pdf->SetRightMargin(15);
    $pdf->SetAutoPageBreak(true, 15);
    // Título
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, utf8_decode('AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES Y TÉRMINOS Y CONDICIONES'), 0, 1, 'C');
    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
        Declaro concurrir de manera libre y voluntaria a la aceptación del presente instrumento, y que no he sido
        inducido/a a error, fuerza, dolo, temor reverencial o coerción alguna que pueda viciar mi consentimiento.  
    ");
    $pdf->MultiCell(0, 4, $contenido);
    $pdf->Ln(3);

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 2, utf8_decode('I.'), 0, 1, 'C');
    $pdf->Ln(3);
    $pdf->Cell(0, 2, utf8_decode('AUTORIZACIÓN PARA EL TRATAMIENTO DE DATOS PERSONALES'), 0, 1, 'C');
    $pdf->Ln(3);

    // Contenido
    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
        Expreso mi consentimiento libre, específico, informado e inequívoco para proporcionar mis datos
        personales y autorizo a la compañía SERVIMATERIALES S.A, con Registro Único de Contribuyentes (RUC)
        Nro. 0992681845001, (En adelante Encargado del tratamiento) para que efectúe el tratamiento de los
        mismos con las finalidades y en las formas establecidas en el presente instrumento.\n
        1. TRATAMIENTO DE DATOS: Acepto y autorizo para que SERVIMATERIALES realice la recogida,
        recopilación procesamiento, obtención, registro, organización, estructuración, conservación,
        almacenamiento, consulta, adaptación, modificación, indexación, extracción, interconexión, utilización,
        posesión, aprovechamiento, acceso, cesión, comunicación, verificación, elaboración de perfiles,
        transferencia Y eliminación de mis datos personales.\n
        Para tales efectos, acepto que SERVIMATERIALES podrá gestionar, obtener, cruzar y validar los datos
        personales que he proporcionado con cualquier entidad pública y/o privada que se encuentre debidamente
        facultada en el país, y, en especial, con la Dirección General de Registro Civil, Identificación y Cedulación,
        la Dirección Nacional de Registros Públicos, así como de otras fuentes legales de información autorizadas
        para operar en el país, de acceso público o a las que SERVIMATERIALES tenga legítimo acceso, y luego de
        mi aceptación puedan ser registrados para el desarrollo legítimo de la relación jurídica o comercial.
        Comprendo y acepto que mi información personal podrá ser almacenada de manera impresa o digital, y
        accederán a ella los funcionarios de SERVIMATERIALES, estando obligados a cumplir con la legislación
        aplicable a las políticas de confidencialidad y protección de datos. \n
        2. FINALIDADES DEL TRATAMIENTO: Conozco y acepto que el tratamiento de mis datos tiene como
        finalidad el procesamiento, análisis, generación de reportes demográficos y de estadísticas, estudios de
        mercado y recepción de anuncios y/o publicidad respecto a los servicios y/o productos que consulte, a fin
        de facilitar, promover, permitir o mantener relaciones con el Proveedor de dichos servicios y/o productos,
        quien se considerará como Responsable del tratamiento de mmis datos personales. Consecuentemente,
        reconozco que el Responsable del tratamiento de mis datos es JANITRONECUADOR S.A., con RUC Nro.
        0992873442001, a quien podré contactar al correo: proveedores@janitronecuador.com y/o al número
        telefónico: +593 98 935 7913.\n
        3. DATOS PERSONALES: Comprendo y autorizo que, para el cumplimiento del presente instrumento, se
        me podrá solicitar la siguiente información personal: nombres y apellidos, número de identificación,
        dirección de correo electrónico, número telefónico, estado civil, edad, fecha de nacimiento, nacionalidad,
        idioma de origen, formación o instrucción académica, títulos profesionales, lugar de trabajo, cargo o
        función desempeñado y datos sensibles como identidad de género, identidad cultural, condición migratoria
        y datos relativos a salud. Como titular de dichos datos, conozco que no estoy obligado/a a aceptar la
        presente autorización ni proporcionar dicha información a menos que requiera consultar y/o acceder a los
        productos y/o servicios de mi interés. \n
        4. DERECHOS: Declaro conocer los derechos que me asisten y que la normativa vigente prevé para acceder,
        rectificar, actualizar, solicitar la eliminación, oponerme, dirigir quejas y recibir los datos personales que he
        proporcionado y que se encuentren en tratamiento. Así mismo, declaro conocer que puedo revocar mi
        consentimiento por escrito, en cualquier momento, sin que sea necesaria una justificación. Para tales fines,
        podré remitir mi requerimiento al correo proveedores@janitronecuador.com en concordancia con los
        procedimientos establecidos en la Ley Orgánica de Protección de Datos Personales (LOPDP) y su
        Reglamento, así como las políticas internas de SERVIMATERIALES.\n
        5. CONSERVACIÓN DE DATOS PERSONALES: SERVIMATERIALES conservará los datos personales durante
        un tiempo no mayor al necesario para cumplir con las finalidades de su tratamiento y el que sea necesario
        para el cumplimiento de disposiciones legales.\n
        6. MEDIDAS DE SEGURIDAD Y CONFIDENCIALIDAD: Conozco que SERVIMATERIALES se compromete a
        implementar y aplicar medidas técnicas y organizativas, adecuadas y necesarias, para la protección de mis
        datos personales, y, especialmente, para evaluar, prevenir, impedir, mitigar y controlar riesgos, amenazas
        y vulnerabilidades; obligación que se hace extensiva al Responsable del tratamiento, de conformidad con
        la LOPDP. Reconozco y acepto que este compromiso no impedirá el tratamiento de mis datos personales
        para las finalidades señaladas.\n
        7. OTRAS DECLARACIONES: Declaro ser el titular de la información reportada; que el tratamiento de mis
        datos personales es legítimo y lícito, el cual se fundamenta en mi consentimiento expreso, libre, específico,
        informado e inequívoco; y, que las finalidades previstas en este instrumento para el tratamiento de sus
        datos personales son determinadas, explícitas, legítimas y que me han sido debidamente comunicadas. De
        igual manera declaro que los datos personales susceptibles a tratamiento son pertinentes y limitados al
        cumplimiento de las finalidades previstas en este instrumento; que el tratamiento es adecuado, necesario,
        oportuno, relevante, y no excesivo, para las finalidades señaladas; que todos los datos personales
        proporcionados son correctos, exactos, íntegros, precisos, completos, comprobables y claros; y, que ha
        sido informado/a acerca de mis derechos como titular y del ejercicio de los mismos.\n

    ");
    $pdf->MultiCell(0, 4, $contenido);
    $pdf->Ln(3);

    // $pdf->AddPage();

    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 10, utf8_decode('II.'), 0, 1, 'C');
    $pdf->Ln(3);
    $pdf->Cell(0, 2, utf8_decode('TÉRMINOS Y CONDICIONES'), 0, 1, 'C');
    $pdf->Ln(3);

    $pdf->SetFont('Arial', '', 9);
    $contenido = utf8_decode("
        1. DEFINICIÓN Y SERVICIOS: El Chatbot es un asistente virtual operado por SERVIMATERIALES S.A. (En
        adelante SERVIMATERIALES) que permite al USUARIO la consulta de los productos y/o servicios ofertados
        o ya contratados de JANITRONECUADOR S.A., con RUC Nro. 0992873442001, en su calidad de PROVEEDOR.
        El Chatbot no requiere ni procesa el pago de productos y/o servicios; por lo que el USUARIO se abstendrá
        de enviar comprobantes de pago u otras transacciones a través del Chatbot. El uso y/o acceso al Chatbot
        por parte del USUARIO no generará costo alguno de ninguna índole.\n
        2. MANEJO DE LA INFORMACIÓN: Sin perjuicio de los datos personales expresados en la Sección I de este
        instrumento, el Chatbot no solicita información como claves, números de cuentas que el USUARIO
        mantenga en instituciones del Sistema Financiero Nacional, números de tarjetas de crédito o de débito,
        reconocimiento facial, código dactilar, huellas u otros datos de carácter confidencial, personal, financiero
        o crediticio del USUARIO. El USUARIO es el único responsable de la veracidad y exactitud de la información
        proporcionada y se abstendrá de proporcionar información no requerida por Chatbot, de manera que
        exime a Chatbot de cualquier responsabilidad contractual o extracontractual, así como de los daños y
        perjuicios que se pudieren ocasionar por el envío de dicha información. No obstante, el Chatbot empleará
        las medidas técnicas de seguridad necesarias para la protección de la información proporcionada por el
        USUARIO y evitar su divulgación y/o uso indebido.\n
        3. CANALES Y HORARIOS: Chatbot funciona vía WHATSAPP®, redirigido desde anuncios en plataformas
        publicitarias de las redes sociales habilitadas para el efecto hasta el momento como: Facebook, Instagram
        y Tiktok. El Chatbot estará disponible las 24 horas del día, los siete días de la semana; no obstante, el
        USUARIO reconoce que, dependiendo del horario en que se realicen las operaciones, estas podrán ser
        procesadas el siguiente día hábil.\n
        4. LÍMITES Y REFORMAS: SERVIMATERIALES podrá modificar los límites y condiciones de los servicios
        brindados a través del Chatbot, como: número máximo de consultas, tiempo de conexión, tipo de consultas
        u operaciones que podrán efectuarse, entre otros que SERVIMATERIALES determine. Dichos límites y
        condiciones entrarán en vigencia y su aplicación será obligatoria para el USUARIO desde que le fueren
        comunicados, a través del Chatbot u otros canales que, en su momento, estuvieren habilitados. Así mismo,
        SERVIMATERIALES se reserva el derecho de modificar los términos y condiciones, los que serán efectivos
        desde su aceptación por parte del USUARIO.\n
        5. MANTENIMIENTO Y SOPORTE: SERVIMATERIALES será responsable del funcionamiento del Chatbot, de
        acuerdo a los controles y resguardos físicos y tecnológicos para su uso seguro, considerando los riesgos
        inherentes a su operatividad. Esta responsabilidad no comprende brindar mantenimiento a los dispositivos,
        plataformas, sistemas, aplicaciones o redes desde las cuales opere el Chatbot.
        6. PRIVACIDAD: SERVIMATERIALES empleará las medidas necesarias para proteger la integridad de la
        información que le ha sido proporcionada, guardando la confidencialidad de la misma, evitando su
        divulgación y/o mal uso a través de las normas técnicas de seguridad requeridas por la ley y según la
        naturaleza de la información lo amerite. \n
        7. LÍMITE DE RESPONSABILIDAD: SERVIMATERIALES no responderá por los daños o perjuicios, directos,
        indirectos o de cualquier naturaleza que el USUARIO pudiera sufrir por las siguientes circunstancias: 7.1. Si
        la consulta u operación requerida por el USUARIO no puede procesarse por no encuadrarse en los
        parámetros prefijados por SERVIMATERIALES. En este sentido, el USUARIO reconoce que el Chatbot es un
        software que ha sido programado por SERVIMATERIALES bajo cierto formato y parámetros
        predeterminados; por lo que cualquier consulta u operación que no se encuadren en dicho formato y
        parámetros, no serán procesados. 7.2. Por incompatibilidad del dispositivo usado por el USUARIO. 7.3. Por
        virus, ataques cibernéticos, vulneraciones, filtraciones u otras intrusiones de seguridad que afecten la
        plataforma de mensajería WHATSAPP®. 7.4. Por problemas o errores operativos o de conectividad de tal
        plataforma o por restricciones impuestas por autoridades administrativas o judiciales para su
        funcionamiento. 7.5. Por la sustracción, pérdida, deterioro o destrucción del dispositivo desde el cual se
        accede al Chatbot. 7.6. Por la sustracción de información o ingreso de la misma por parte de terceros
        diferentes al USUARIO, titular de dicha información. SERVIMATERIALES presume que el USUARIO que
        realiza la consulta u operación es el titular de la información que proporciona y/o requiere o, en su defecto,
        que ha sido expresamente autorizado por el titular de la misma para proporcionarla y/o para recibirla del
        Chatbot. 7.7. Por la calidad del servicio de telefonía o de Internet utilizado para la conectividad y acceso al
        Chatbot. 7.8. Por el uso o destino que el USUARIO dé a la información recibida u operaciones realizadas a
        través del Chatbot. 7.9. Por la calidad, precios y atención respecto a los productos y/o servicios del
        PROVEEDOR, ya que los mismos no son ofertados por SERVIMATERIALES. El USUARIO comprende y
        reconoce que SERVIMATERIALES y el PROVEEDOR son personas jurídicas distintas y, por ende, no existe
        relación comercial o vinculación alguna entre ellas para la oferta de los productos y servicios que le
        pertenecen al PROVEEDOR. La intervención de SERVIMATERIALES se limita única y exclusivamente a la
        operatividad del Chatbot como medio para de realización de consultas y operaciones relacionadas a
        productos y/o servicios de terceros. 7.10. Por cualquier otro hecho u actuación no imputable a
        SERVIMATERIALES.\n
        8. AUTORIZACIÓN: El USUARIO autoriza expresamente a SERVIMATERIALES a grabar, captar, registrar y/o
        almacenar los datos y respuestas dadas en el Chatbot, así como las solicitudes, autorizaciones y, en general,
        las instrucciones impartidas a través de tal medio y a reproducirlas y/o mostrarlas en la medida que sea
        necesario aclarar, explicar, demostrar, probar y/o verificar sus actividades en el Chatbot, en especial, ante
        cualquier autoridad fiscal o judicial.
        Declaro haber leído y comprendido cada una de las cláusulas y demás declaraciones que constan en este
        instrumento sobre la autorización y tratamiento de mis datos personales, así como sobre la operatividad
        del Chatbot. Consecuentemente, con pleno conocimiento de las obligaciones y compromisos que me
        corresponden, acepto el servicio Chatbot bajo los términos y condiciones que me han sido expuestos.\n
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
    $pdf->Cell(0, 6, "      " . utf8_decode($nombre) . " - " . $CEDULA, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, "      " . utf8_decode('ACEPTÓ TERMINOS Y CONDICIONES: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6, "      " . $fechaConsulta, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, utf8_decode('      DIRECCIÓN IP: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6,  "      " . $ip, 0, 1, 'L');
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(0, 5, utf8_decode('      NUMERO: '), 0, 1, 'L');
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(0, 6,  "      " . $NUMERO, 0, 1, 'L');


    $nombreArchivo = $CEDULA . "_" . $fecha . "_" . $EMP . ".pdf"; // Nombre del archivo PDF
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
