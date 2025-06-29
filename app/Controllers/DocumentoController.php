<?php

namespace App\Controllers;

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use App\Controllers\EmailController;
use PhpOffice\PhpWord\Settings;

class DocumentoController {

    public function __construct()
    {
        
    }

    //Genera nuevo documento cambiando valores a la plantilla base, y envia mail con el documento adjunto
    public function getTemplate() : void{
        $num_cuenta = str_pad($this->getUltimoDoc(), 3, '0', STR_PAD_LEFT);
        $date_now = date('Y') .'-' . date('m') . '-' . date('d');
        $month = $this->getMonth(date('m'));
        $fecha = $this->dateToWord($date_now);
        $quincena = date('d') <= 15 ? 'primera' : 'segunda';

        $template = $_ENV['TEMPLATE'];
        $newDoc = $_ENV['DOCFINAL'];
        
        $templateProcessor = new TemplateProcessor(__DIR__ .'/../../storage/plantillas/'.$template.'.docx');
        $wordGenerate = __DIR__ .'/../../storage/cuentas/'.$newDoc.' '.$num_cuenta.'.docx';
        $pdfGenerate = __DIR__ .'/../../storage/cuentas/'.$newDoc.' '.$num_cuenta.'.pdf';

        // Reemplazar variables
        $templateProcessor->setValue('NUMERO_CUENTA_COBRO', $num_cuenta);
        $templateProcessor->setValue('FECHA', $fecha);

        // Guardar nuevo archivo
        $templateProcessor->saveAs($wordGenerate);

        //convertir a pdf
        if(file_exists($wordGenerate)){
            $this->wordToPdf($wordGenerate,$pdfGenerate);
        }
        

        if(file_exists($pdfGenerate)){
            //sendEmail
            $subject = 'Cuenta de cobro ' . $num_cuenta . ' ' . $month .' de ' . date('Y');
            $fromName = $_ENV['FROMNAME'] ?? '';
            $body = "<p>Cordial saludo,</p><br>".
            "<p>Adjunto cuenta de cobro correspondiente a la ".$quincena." quincena del mes relacionado en el asunto, correspondiente a servicios prestados en la compañía.</p><br>".
            "<p>Muchas gracias</p><br>".
            "<p>Atentamente</p><br>".
            "<p>".$fromName."</p>".
            "<p>Desarrollador</p>";

            $this->sendEmail($subject,$body,$pdfGenerate);
        }
        

    }

    //Obtener el ultimo documento generado
    private function getUltimoDoc() :int {
        $directorio = __DIR__ . '/../../storage/cuentas'; 
        $archivos = scandir($directorio);

        // Filtrar '.' y '..'
        $archivos = array_diff($archivos, ['.', '..']);
        $ultimo_doc = end($archivos);

        $num_cuenta = substr($ultimo_doc,27,3);

        return (int) $num_cuenta + 1;
    }

    //dar formato a la fecha
    private function dateToWord(string $stringDate, string $formatDate = 'DD de MM de AAAA'): ?string
    {

        //Parsear la fecha de entrada
        $dateObj = \DateTime::createFromFormat('Y-m-d', $stringDate);

        // Validar si la fecha es correcta y coincide con el formato esperado
        if (!$dateObj || $dateObj->format('Y-m-d') !== $stringDate) {
            trigger_error('Formato de fecha inválido o fecha no válida. Se esperaba YYYY-MM-DD.', E_USER_WARNING);
            return null;
        }

        //Extraer día, mes y año
        $dayNum = (int)$dateObj->format('d');
        $monthNum = (int)$dateObj->format('m');
        $yearNum = (int)$dateObj->format('Y');

        
        $monthWord = $this->getMonth($monthNum);

        //Construir string de salida según el formato especificado
        // Crear un array de búsqueda y reemplazo para strtr (más eficiente)
        $replace = [
            'DD'   => $dayNum,
            'MM'   => $monthWord,
            'AAAA' => $yearNum
        ];

        // Reemplazar los placeholders en la cadena de formato
        $dateWord = strtr($formatDate, $replace);

        return $dateWord;
    }

    //Obtener el mes actual en texto
    private function getMonth(int $month) :string{
        //Convertir componentes a letras
        // Meses en español
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $monthWord = $months[$month] ?? 'Mes inválido'; 
    }

    //Enviar email
    private function sendEmail($subject,$body,$attachment){
        $mailer = new EmailController();
        $mailer->sendEmail($subject,$body,$attachment);
    }

    //convertir docx a pdf
    private function wordToPdf($wordFile,$pdfFile){
        try {
            // Indicar el motor PDF y su ruta
            Settings::setPdfRendererName(Settings::PDF_RENDERER_DOMPDF);
            Settings::setPdfRendererPath(__DIR__ . '/../../vendor/dompdf/dompdf');

            //cargar docx
            $phpWord = IOFactory::load($wordFile);

            //convertir a PDF
            $pdfWriter = IOFactory::createWriter($phpWord, 'PDF');
            
            // Guardar el archivo PDF.
            $pdfWriter->save($pdfFile);
        }
        catch(\Exception $e){
            echo "Error al generar el PDF";
        }
    }
}