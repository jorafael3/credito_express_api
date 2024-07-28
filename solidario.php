<?php
include 'funciones.php';

if (isset($_GET["cedula"])) {
    $CEDULA = trim($_GET["cedula"]);
    if ($CEDULA != null || $CEDULA != "") {

        $longitud = strlen($CEDULA);
        // echo "La longitud del string es: " . $longitud;
        if ($longitud == 9) {
            $CEDULA = "0" . $CEDULA;
        }
        date_default_timezone_set('America/Guayaquil');
        // Obtén la hora actual
        $currentDateTime = new DateTime();
        $currentHour = (int)$currentDateTime->format('H');
        // Determina si la hora actual es mayor o igual a las 21:00 o menor o igual a las 6:00
        if ($currentHour >= 21 || $currentHour <= 6) {
            $res = array(
                "SUCCESS" => "0",
                "MENSAJE" => "SU CONSULTA SERA PROCESADA EN EL SIGUIENTE DIA HABIL5"
            );
            Guardar_Cedula_9pm($CEDULA);
            echo json_encode($res);
            exit();
        } else {
            Principal($CEDULA);
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
        "MENSAJE" => "URL NO VALIDA"
    );

    echo json_encode($res);
    exit();
}

