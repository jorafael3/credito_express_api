<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';



function ENVIAR_CORREO_CREDITO()
{

    try {




        $numero_salv = "093 989 7277";
        $nombre_cliente = "jorg ala";
        $img = "C:\xampp\htdocs\credito_express_api\SV24-LogosLC_Credito.png";


        $html_disponible = "  
        <h1 style='text-align: center; color: #007bff;'>Felicidades!</h1>
        <p style='text-align: justify;'>Estimado/a " . $nombre_cliente . ",</p>
        <p style='text-align: justify;'>Nos complace informarte que tienes un <strong>crédito disponible</strong> con Salvacero.</p>
        <p style='text-align: justify;'>Nuestro equipo está comprometido en brindarte el mejor servicio y apoyo en todo momento. Estamos listos para guiarte a través del proceso y responder a todas tus preguntas para que puedas acceder a los fondos que necesitas de manera rápida y sencilla.</p>
        <p style='text-align: justify;'>Para obtener más información sobre tu crédito disponible y cómo puedes acceder a él, no dudes en ponerte en contacto con nosotros llamando al siguiente número: " . $numero_salv . ". Alternativamente, nuestro equipo se pondrá en contacto contigo para brindarte más detalles y asistencia.</p>
        <p style='text-align: justify;'>¡Gracias por utilizar este servicio!</p>
        <p style='text-align: justify;'>Saludos cordiales,<br>Equipo de Salvacero</p>";

        $html_no = " 
        <h1 style='text-align: center; color: #e74c3c;'>¡Lo sentimos!</h1>
        <p style='text-align: justify;'>Estimado/a " . $nombre_cliente . ",</p>
        <p style='text-align: justify;'>Lamentablemente, en este momento no tienes un crédito disponible con Salvacero.</p>
        <p style='text-align: justify;'>No te desanimes, estamos aquí para ayudarte en todo lo que podamos. Si tienes alguna pregunta o necesitas asistencia adicional, no dudes en ponerte en contacto con nosotros. Nuestro equipo estará encantado de ayudarte en lo que necesites.</p>
        <p style='text-align: justify;'>Te agradecemos por confiar en Salvacero y esperamos poder brindarte nuestro apoyo en el futuro.</p>
        <p style='text-align: justify;'>Saludos cordiales,<br>Equipo de Salvacero</p>";

        $msg = "
            <!DOCTYPE html>
            <html lang='es'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Correo Electrónico de Ejemplo</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background-image: url('SV24-LogosLC_Credito.png');
                        background-repeat: no-repeat;
                        background-size: cover;
                        padding: 20px;
                    }
                    .container {
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: #fff;
                        padding: 20px;
                        border-radius: 10px;
                        box-shadow: 0 0 10px rgba(0,0,0,0.1);
                    }
                    h1 {
                        text-align: center;
                        color: #007bff;
                    }
                    p {
                        text-align: justify;
                    }
                </style>
            </head>
            <body style='font-family: Arial, sans-serif; background-color: #2471A3; color: #333; padding: 20px;'>

            <div style='max-width: 600px; margin: 0 auto; background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>
                <img src='https://salvacerohomecenter.com/img/cms/SV23%20-%20Logo%20Web_3.png' alt='Logo Salvacero' style='display: block; margin: 0 auto; max-width: 200px;'>
                    " . $html_disponible . "
            </div>

            </body>
            </html>
    ";

        $m = new PHPMailer(true);
        $m->CharSet = 'UTF-8';
        $m->isSMTP();
        $m->SMTPAuth = true;
        $m->Host = 'mail.creditoexpres.com';
        $m->Username = 'estadodecredito@creditoexpres.com';
        // $m->Password = 'izfq lqiv kbrc etsx';
        $m->Password = 'S@lvacero2024*';
        $m->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $m->Port = 465;
        $m->setFrom('estadodecredito@creditoexpres.com', 'Credito Salvacero');
        $m->addAddress('jalvaradoe3@gmail.com');
        $m->isHTML(true);
        $titulo = strtoupper('Estado del credito solicitado');
        $m->Subject = $titulo;
        $m->Body = $msg;
        //$m->addAttachment($atta);
        // $m->send();
        if ($m->send()) {
            echo "<pre>";
            $mensaje = ("Correo enviado ");
            echo "</pre>";
            echo $mensaje;
            // return 1;

        } else {
            echo "Ha ocurrido un error al enviar el correo electrónico.";
            // return 0;
        }
    } catch (Exception $e) {
        $e = $e->getMessage();
        return $e;
    }
}

// ENVIAR_CORREO_CREDITO();

function Get_Email($ID_UNICO)
{
    require('conexion.php');

    try {
        // sleep(4);
        // $cedula = trim($param["cedula"]);
        $arr = "";
        $query = $pdo->prepare("SELECT ifnull(correo,'')as correo, nombre_cliente FROM creditos_solicitados
        WHERE ID_UNICO = :ID_UNICO");
        $query->bindParam(":ID_UNICO", $ID_UNICO, PDO::PARAM_STR);
        if ($query->execute()) {
            $result = $query->fetchAll(PDO::FETCH_ASSOC);
            if (count($result) > 0) {
                $co = $result[0]["correo"];
                if ($co == "") {
                    return 0;
                } else {
                    return $result;
                }
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    } catch (PDOException $e) {
        $e = $e->getMessage();
        return [0, "INTENTE DE NUEVO"];
    }
}

var_dump(Get_Email("202405081005470931531115"));

