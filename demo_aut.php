<?php

function Cargar()
{
    require('conexion.php');

    try {
        $con = 0;

        $SQL = "SELECT * FROM encript_agua 
        where URL_CONSULTA = 'DEMO' 
        and ifnull(datos,'') = ''
        LIMIT 100";

        $SQL = "SELECT * FROM encript_agua 
            where ifnull(datos,'') = ''
            LIMIT 100";


        $query = $pdo->prepare($SQL);
        if ($query->execute()) {
            $result = $query->fetchAll(PDO::FETCH_ASSOC);

            foreach ($result as $row) {
                $ID_UNICO = trim($row["ID_UNICO"]);
                $cedula_encr = trim($row["cedula_encrypt"]);
                $DEM = CONSULTA_API_REG_DEMOGRAFICO($cedula_encr);
                // echo json_encode($DEM[1]);
                // exit();
                Actualizar_DatosDemo([$DEM[1]], $ID_UNICO);
                $con++;
            }
        }
    } catch (PDOException $e) {
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
            if (isset($data["SOCIODEMOGRAFICO"])) {
                $data["SOCIODEMOGRAFICO"][0]["CALLENUM"] = $data["SOCIODEMOGRAFICO"][0]["CALLE"] . " NUM " . $data["SOCIODEMOGRAFICO"][0]["NUM"];
                $data["SOCIODEMOGRAFICO"][0]["CALLE_NUM"] = $data["SOCIODEMOGRAFICO"][0]["CALLE"] . " NUM " . $data["SOCIODEMOGRAFICO"][0]["NUM"];
                $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM_PROVINCIA"] = explode('/', $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM"])[0];
                $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM_CIUDAD"] = explode('/', $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM"])[1];
                $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM_PARROQUIA"] = explode('/', $data["SOCIODEMOGRAFICO"][0]["LUGAR_DOM"])[2];
            }


            // echo json_encode($data);
            // exit();
            return [1, $data];
        }
        // Cerrar cURL
        curl_close($ch);
    } catch (Exception $e) {
        $e = $e->getMessage();
        return [0, $e];
    }
}

function Actualizar_DatosDemo($DATOS, $ID_UNICO)
{
    require('conexion.php');

    try {
        $arr = "";
        $DATOS = json_encode($DATOS);
        $query = $pdo->prepare("UPDATE encript_agua
        SET
            datos = :datos
        WHERE 
            ID_UNICO = :ID_UNICO
        ");
        $query->bindParam(":datos", $DATOS, PDO::PARAM_STR);
        $query->bindParam(":ID_UNICO", $ID_UNICO, PDO::PARAM_STR);
        if ($query->execute()) {
            $result = $query->fetchAll(PDO::FETCH_ASSOC);
            return $result;
        }
    } catch (PDOException $e) {
        $e = $e->getMessage();
        return [0, "INTENTE DE NUEVO"];
    }
}

Cargar();
