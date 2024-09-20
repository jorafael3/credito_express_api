<?php
require('fpdf/fpdf.php');

function justificarTexto($pdf, $texto, $ancho, $altoLinea) {
    // Divide el texto en párrafos
    $parrafos = explode("\n", $texto);
    
    foreach ($parrafos as $parrafo) {
        // Divide el párrafo en palabras
        $palabras = explode(' ', $parrafo);
        $linea = '';
        foreach ($palabras as $palabra) {
            // Calcula el ancho de la línea actual más la palabra
            $lineaConPalabra = $linea . ($linea ? ' ' : '') . $palabra;
            $anchoLinea = $pdf->GetStringWidth($lineaConPalabra);
            
            // Si el ancho supera el límite, imprime la línea actual y empieza una nueva
            if ($anchoLinea > $ancho) {
                // Justifica la línea
                justificarLinea($pdf, $linea, $ancho, $altoLinea);
                $linea = $palabra;
            } else {
                $linea = $lineaConPalabra;
            }
        }
        
        // Imprime la última línea del párrafo alineada a la izquierda sin justificar
        $pdf->Cell($ancho, $altoLinea, $linea, 0, 1, 'L');
    }
}

function justificarLinea($pdf, $linea, $ancho, $altoLinea) {
    $palabras = explode(' ', $linea);
    $numeroPalabras = count($palabras);
    
    if ($numeroPalabras == 1) {
        // Si es solo una palabra, imprime sin justificación
        $pdf->Cell($ancho, $altoLinea, $linea, 0, 1, 'L');
    } else {
        // Calcula el ancho total de la línea sin espacios
        $anchoLinea = 0;
        foreach ($palabras as $palabra) {
            $anchoLinea += $pdf->GetStringWidth($palabra);
        }
        
        // Calcula el espacio adicional entre palabras
        $espacioExtra = ($ancho - $anchoLinea) / ($numeroPalabras - 1);
        
        // Imprime cada palabra con el espacio extra calculado
        foreach ($palabras as $i => $palabra) {
            if ($i == $numeroPalabras - 1) {
                // La última palabra va sin espacio extra
                $pdf->Cell($pdf->GetStringWidth($palabra), $altoLinea, $palabra, 0, 1, 'L');
            } else {
                $pdf->Cell($pdf->GetStringWidth($palabra) + $espacioExtra, $altoLinea, $palabra, 0, 0, 'L');
            }
        }
    }
}

// Inicializa el PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 9);

$texto = utf8_decode("
Declaro que soy el titular de la información reportada, y que la he suministrado de forma voluntaria, completa,
confiable, veraz, exacta y verídica.
Como titular de los datos personales, particularmente el código dactilar, dato biométrico facial, no me encuentro
obligado a otorgar mi autorización de tratamiento a menos que requiera consultar y/o aplicar a un producto y/o
servicio financiero. A través de la siguiente autorización libre, especifica, previa, informada, inequívoca y explícita,
faculto al tratamiento (recopilación, acceso, consulta, registro, almacenamiento, procesamiento, análisis, elaboración
de perfiles, comunicación o transferencia y eliminación) de mis datos personales incluido el código dactilar con la
finalidad de: consultar y/o aplicar a un producto y/o servicio financiero y ser sujeto de decisiones basadas única o
parcialmente en valoraciones que sean producto de procesos automatizados, incluida la elaboración de perfiles. Esta
información será conservada por el plazo estipulado en la normativa aplicable.
Así mismo, declaro haber sido informado por el BANCO de los derechos con que cuento para conocer, actualizar y
rectificar mi información personal, así como, los establecidos en el artículo 20 de la LOPDP y remitir mi requerimiento
a través del proceso de atención de derechos ARSO+; en cualquier momento y sin costo alguno, utilizando la página
web (www.banco-solidario.com), teléfono: 1700 765 432, comunicado escrito o en cualquiera de las agencias del
BANCO.
Para proteger esta información conozco que el Banco cuenta con medidas técnicas y organizativas de seguridad
adaptadas a los riesgos como, por ejemplo: anonimización, cifrado, enmascarado y seudonimización.
Con la lectura de este documento manifiesto que he sido informado sobre el Tratamiento de mis Datos Personales, y
otorgo mi autorización y aceptación de forma voluntaria y verídica. En señal de aceptación suscribo el presente
documento.
");

$ancho = 190; // Ancho de la celda
$altoLinea = 5; // Altura de la línea

// Llama a la función para justificar el texto
justificarTexto($pdf, $texto, $ancho, $altoLinea);

// Salida del PDF

$nombreArchivo = "0931531115" . ".pdf"; // Nombre del archivo PDF
$rutaCarpeta = 'docs/'; // Ruta de la carpeta donde se guardará el archivo (debes cambiar esto)


$pdf->Output($rutaCarpeta . $nombreArchivo, 'F');