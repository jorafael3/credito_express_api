<?php


function inicio()
{
}

function API()
{
    require_once("funciones.php");
    // $CEDULA = "0931531115";
    $CEDULAS = get_Cedula_9pm();
    $ARRAY_DATOS = [];
    if (count($CEDULAS) > 0) {
        foreach ($CEDULAS as $row) {
            $DATOS = _9pm($row["cedula"]);
            // echo json_encode($DATOS);
            array_push($ARRAY_DATOS, $DATOS);
        }
        echo json_encode($ARRAY_DATOS[1]);
        exit();
    } else {
        $res = array(
            "SUCCESS" => "0",
            "MENSAJE" => "NO HAY REGISTROS PARA HACER CONSULTA"
        );
        echo json_encode($res);
        exit();
    }



    // _9pm($CEDULA);
}

function get_Cedula_9pm()
{
    require('conexion.php');

    try {
        $arr = "";
        $query = $pdo->prepare("SELECT DISTINCT cedula
            FROM encript_agua ea 
            WHERE 
            (DATE(fecha) = CURDATE() AND despues_9 = 1)
                OR
            (DATE(fecha) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) AND TIME(fecha) >= '21:00:00' AND despues_9 = 1)");

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
API();
