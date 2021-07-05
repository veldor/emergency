<?php


namespace app\models\utils;


use Dompdf\Dompdf;

class PdfHandler
{
    /**
     * @param $text
     * @param $filename
     * @param $orientation
     */
    public static function renderPDF($text, $filename, $orientation): void
    {
        $dompdf = new Dompdf([
            'defaultFont' => 'times',//делаем наш шрифт шрифтом по умолчанию
        ]);
        $dompdf->loadHtml($text);
// (Optional) Setup the paper size and orientation
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        $output = $dompdf->output();
        file_put_contents($filename, $output);
    }
}