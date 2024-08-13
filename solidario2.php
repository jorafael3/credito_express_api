<?php
include 'funciones.php';

if (isset($_GET["cedula"]) && isset($_GET["numero"]) && isset($_GET["key"]) && isset($_GET["fecha"]) && isset($_GET["ingresos"])) {
    $CEDULA = trim($_GET["cedula"]);
    $NUMERO = trim($_GET["numero"]);
    $FECHA = trim($_GET["fecha"]);
    $INGRESOS = trim($_GET["ingresos"]);


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

            $CEN = encryptCedula($CEDULA);
            if ($CEN[0] == 1) {

                $DATOS_CREDITO  = Obtener_Datos_Credito($CEN[1], $NUMERO, $FECHA, $INGRESOS);

                echo json_encode($DATOS_CREDITO);
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
