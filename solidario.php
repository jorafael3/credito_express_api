<?php
include 'funciones.php';

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

        date_default_timezone_set('America/Guayaquil');
        // Obtén la hora actual
        $currentDateTime = new DateTime();
        $currentHour = (int)$currentDateTime->format('H');

        // if ($currentHour >= 21 || $currentHour <= 6) {
        //     $res = array(
        //         "SUCCESS" => "0",
        //         "MENSAJE" => "SU CONSULTA SERA PROCESADA EN EL SIGUIENTE DIA HABIL"
        //     );
        //     Guardar_Cedula_9pm($CEDULA);
        //     echo json_encode($res);
        //     exit();
        // } else {
        //     Principal($CEDULA);
        // }
        Principal($CEDULA, $NUMERO);
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
